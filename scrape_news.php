<?php
function scrape_latest_news() {
    // Path to your Python script
    $python_script = '/path/to/news_scraper.py';
    
    // Execute Python script
    $output = shell_exec("python3 $python_script 2>&1");
    
    // Read the generated JSON
    $news_file = 'latest_news.json';
    
    if (file_exists($news_file)) {
        $news_json = file_get_contents($news_file);
        return json_decode($news_json, true);
    }
    
    // Fallback to default news if scraping fails
    return [
        [
            'title' => 'Global News Update',
            'description' => 'Unable to fetch live news. Please check your internet connection.',
            'source' => 'Newsilo System',
            'category' => 'general'
        ]
    ];
}
?>
