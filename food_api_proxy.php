<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

header('Content-Type: application/json');

define('USDA_API_KEY', 'gducgCy2bFRhItgwA7q9RVdZiFAxAFfYkLHOohw6');

$q   = trim($_GET['q']   ?? '');
$qty = floatval($_GET['qty'] ?? 100);

if ($q === '') { echo json_encode([]); exit; }

$results = [];

/* ---- 1. Search local Indian foods table first ---- */
$qEsc = mysqli_real_escape_string($conn, $q);
$res  = mysqli_query($conn, "SELECT * FROM indian_foods WHERE name LIKE '%$qEsc%' LIMIT 10");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $results[] = [
            'name'        => $row['name'],
            'brand'       => null,
            'cal_per_100' => intval($row['cal_per_100']),
            'protein100'  => floatval($row['protein100']),
            'carbs100'    => floatval($row['carbs100']),
            'fat100'      => floatval($row['fat100']),
        ];
    }
}

/* ---- 2. USDA FoodData Central ---- */
if (empty($results)) {

    /* Helper: run one USDA search call for a given dataType filter */
    $usdaSearch = function ($q, $dataType) {
        $url = 'https://api.nal.usda.gov/fdc/v1/foods/search?'
             . http_build_query([
                   'query'    => $q,
                   'api_key'  => USDA_API_KEY,
                   'pageSize' => 25, // pull extra so we can rank & trim to 10
                   'dataType' => $dataType,
               ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'LifeSync/1.0',
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        if (!$raw) return [];
        $data = json_decode($raw, true);
        return (!empty($data['foods']) && is_array($data['foods'])) ? $data['foods'] : [];
    };

    /* Helper: convert a raw USDA food item into our normalized shape, or null if no calorie data */
    $normalize = function ($item) {
        $name = trim($item['description'] ?? '');
        if ($name === '') return null;

        $cal = 0; $protein = 0; $carbs = 0; $fat = 0;
        foreach ($item['foodNutrients'] ?? [] as $n) {
            $id  = $n['nutrientId'] ?? $n['nutrientNumber'] ?? 0;
            $val = floatval($n['value'] ?? 0);
            // Nutrient IDs: 1008=Energy(kcal), 1003=Protein, 1005=Carbs, 1004=Fat
            // nutrientNumber strings: '208'=Energy, '203'=Protein, '205'=Carbs, '204'=Fat
            $num = strval($n['nutrientNumber'] ?? '');
            if ($id == 1008 || $num === '208') $cal     = $val;
            if ($id == 1003 || $num === '203') $protein = $val;
            if ($id == 1005 || $num === '205') $carbs   = $val;
            if ($id == 1004 || $num === '204') $fat     = $val;
        }
        if ($cal <= 0) return null;

        return [
            'name'        => ucwords(strtolower($name)),
            'brand'       => trim($item['brandOwner'] ?? $item['brandName'] ?? '') ?: null,
            'cal_per_100' => round($cal),
            'protein100'  => round($protein, 1),
            'carbs100'    => round($carbs, 1),
            'fat100'      => round($fat, 1),
            '_dataType'   => $item['dataType'] ?? '',
            '_origName'   => $name,
        ];
    };

    /* Helper: relevance score — lower is better.
       Prefers exact name match, then "name starts with query", then shorter
       names (e.g. "Orange, raw" ranks above "Orange-flavored gelatin dessert mix"). */
    $relevanceScore = function ($item, $q) {
        $n = strtolower($item['_origName']);
        $qLower = strtolower($q);
        $score = 100;
        if ($n === $qLower) $score = 0;
        elseif (str_starts_with($n, $qLower . ',')) $score = 1;   // "Orange, raw"
        elseif (str_starts_with($n, $qLower)) $score = 2;          // "Oranges raw"
        elseif (str_contains($n, $qLower)) $score = 5;
        // Branded/processed items are deprioritized relative to generic foods
        if (($item['_dataType'] ?? '') === 'Branded') $score += 3;
        // Slight penalty for longer descriptions (usually more processed/composite items)
        $score += min(strlen($n) / 40, 3);
        return $score;
    };

    /* Step 2a: search generic/whole foods first (Foundation + SR Legacy) */
    $foods = $usdaSearch($q, 'Foundation,SR Legacy');

    /* Step 2b: only fall back to Branded (packaged/processed) items if nothing generic found */
    if (empty($foods)) {
        $foods = $usdaSearch($q, 'Branded');
    }

    $normalized = [];
    foreach ($foods as $item) {
        $n = $normalize($item);
        if ($n !== null) $normalized[] = $n;
    }

    usort($normalized, function ($a, $b) use ($relevanceScore, $q) {
        return $relevanceScore($a, $q) <=> $relevanceScore($b, $q);
    });

    $normalized = array_slice($normalized, 0, 10);

    foreach ($normalized as $n) {
        unset($n['_dataType'], $n['_origName']);
        $results[] = $n;
    }
}

/* ---- 3. Open Food Facts fallback (branded/packaged products) ---- */
if (empty($results)) {
    $url = 'https://world.openfoodfacts.org/cgi/search.pl?'
         . http_build_query([
               'search_terms'  => $q,
               'search_simple' => 1,
               'action'        => 'process',
               'json'          => 1,
               'page_size'     => 10,
               'fields'        => 'product_name,brands,nutriments',
           ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'LifeSync/1.0',
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw) {
        $data = json_decode($raw, true);
        if (!empty($data['products']) && is_array($data['products'])) {
            foreach ($data['products'] as $item) {
                $name = trim($item['product_name'] ?? '');
                if ($name === '') continue;

                $n   = $item['nutriments'] ?? [];
                $cal = floatval($n['energy-kcal_100g'] ?? 0);
                if ($cal <= 0 && isset($n['energy_100g'])) {
                    $cal = round(floatval($n['energy_100g']) / 4.184);
                }
                if ($cal <= 0) continue;

                $results[] = [
                    'name'        => $name,
                    'brand'       => trim($item['brands'] ?? '') ?: null,
                    'cal_per_100' => round($cal),
                    'protein100'  => round(floatval($n['proteins_100g']      ?? 0), 1),
                    'carbs100'    => round(floatval($n['carbohydrates_100g'] ?? 0), 1),
                    'fat100'      => round(floatval($n['fat_100g']           ?? 0), 1),
                ];
            }
        }
    }
}

echo json_encode($results);