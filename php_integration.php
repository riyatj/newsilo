<?php
// news_functions.php - Functions for fetching and displaying news

/**
 * Fetch news articles from the Python API
 * 
 * @param string $category Optional category filter
 * @param string $source Optional source filter
 * @param int $limit Maximum number of articles to fetch
 * @param int $offset Pagination offset
 * @return array Array of news articles
 */
function fetchNews($category = null, $source = null, $limit = 10, $offset = 0) {
    $apiUrl = "http://localhost:5000/api/news?";
    
    $params = array();
    if ($category) {
        $params[] = "category=" . urlencode($category);
    }
    
    if ($source) {
        $params[] = "source=" . urlencode($source);
    }
    
    $params[] = "limit=" . intval($limit);
    $params[] = "offset=" . intval($offset);
    
    $apiUrl .= implode("&", $params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($error) {
        error_log("cURL Error: " . $error);
        return array('error' => 'Failed to connect to news service');
    }
    
    if ($httpCode != 200) {
        error_log("API returned status code: " . $httpCode);
        return array('error' => 'News service returned error (code ' . $httpCode . ')');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return array('error' => 'Invalid response from news service');
    }
    
    return isset($data['articles']) ? $data['articles'] : array();
}

/**
 * Fetch all available news categories
 * 
 * @return array Array of category names
 */
function fetchCategories() {
    $apiUrl = "http://localhost:5000/api/categories";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        return array();
    }
    
    $data = json_decode($response, true);
    
    return isset($data['categories']) ? $data['categories'] : array();
}

/**
 * Check if the news service is available
 * 
 * @return bool True if the service is available
 */
function isNewsServiceAvailable() {
    $apiUrl = "http://localhost:5000/api/status";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($httpCode != 200) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    return isset($data['status']) && $data['status'] === 'ok';
}

/**
 * Create a formatted HTML card for a news article
 * 
 * @param array $article The news article data
 * @return string HTML content
 */
function renderNewsCard($article) {
    $title = htmlspecialchars($article['title']);
    $source = htmlspecialchars($article['source']);
    $url = htmlspecialchars($article['url']);
    $summary = isset($article['summary']) ? htmlspecialchars($article['summary']) : '';
    $imageUrl = isset($article['image_url']) && $article['image_url'] 
        ? htmlspecialchars($article['image_url']) 
        : 'images/default-news.jpg';
    
    $date = isset($article['published_date']) 
        ? date('M j, Y', strtotime($article['published_date'])) 
        : '';
    
    return <<<HTML
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="{$imageUrl}" class="card-img-top" alt="{$title}" onerror="this.src='images/default-news.jpg';">
            <div class="card-body">
                <h5 class="card-title">{$title}</h5>
                <p class="card-text text-truncate">{$summary}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">{$source}</small>
                    <small class="text-muted">{$date}</small>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0">
                <a href="{$url}" class="btn btn-outline-dark w-100" target="_blank">Read More</a>
            </div>
        </div>
    </div>
HTML;
}
