import requests
from bs4 import BeautifulSoup
import random
import json

def get_limited_news():
    try:
        # Scrape from multiple sources
        url_sources = [
            "https://www.bbc.com/news",
            "https://www.cnn.com/"
        ]
        
        all_articles = []
        
        for url in url_sources:
            response = requests.get(url)
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Different scraping logic for different sources
            if 'bbc.com' in url:
                articles = _scrape_bbc(soup)
            elif 'cnn.com' in url:
                articles = _scrape_cnn(soup)
            
            all_articles.extend(articles)
        
        # Shuffle and limit to 3 articles
        random.shuffle(all_articles)
        return all_articles[:3]
    
    except Exception as e:
        print(f"Error scraping news: {e}")
        return []

def _scrape_bbc(soup):
    articles = []
    for article in soup.find_all('div', class_='gs-c-promo')[:5]:
        title_elem = article.find(['h2', 'h3'])
        link_elem = article.find('a', href=True)
        image_elem = article.find('img')
        
        if title_elem and link_elem:
            title = title_elem.get_text(strip=True)
            link = f"https://www.bbc.com{link_elem['href']}" if not link_elem['href'].startswith('http') else link_elem['href']
            image = image_elem['src'] if image_elem and 'src' in image_elem.attrs else ''
            
            articles.append({
                'title': title,
                'link': link,
                'image': image,
                'source': 'BBC News',
                'description': 'Breaking news from BBC. Login to read more.'
            })
    
    return articles

def _scrape_cnn(soup):
    articles = []
    for article in soup.find_all('article', class_='card')[:5]:
        title_elem = article.find('span', class_='cd__headline-text')
        link_elem = article.find('a', href=True)
        image_elem = article.find('img')
        
        if title_elem and link_elem:
            title = title_elem.get_text(strip=True)
            link = link_elem['href'] if link_elem['href'].startswith('http') else f"https://www.cnn.com{link_elem['href']}"
            image = image_elem['src'] if image_elem and 'src' in image_elem.attrs else ''
            
            articles.append({
                'title': title,
                'link': link,
                'image': image,
                'source': 'CNN',
                'description': 'Latest news from CNN. Login to read more.'
            })
    
    return articles

# This function will be called from PHP
def main():
    news = get_limited_news()
    print(json.dumps(news))

if __name__ == "__main__":
    main()
