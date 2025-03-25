import requests
from bs4 import BeautifulSoup
import json
from datetime import datetime
import os
import time
import mysql.connector
import random
from urllib.parse import urljoin

class NewsAggregator:
    def __init__(self, db_config):
        """Initialize the news aggregator with database configuration."""
        self.db_config = db_config
        # Updated source list to include Indian news websites across categories
        self.sources = {
            "general": [
                {"url": "https://www.thehindu.com/", "site": "The Hindu"},
                {"url": "https://timesofindia.indiatimes.com/", "site": "Times of India"},
                {"url": "https://indianexpress.com/", "site": "Indian Express"},
                {"url": "https://www.hindustantimes.com/", "site": "Hindustan Times"}
            ],
            "business": [
                {"url": "https://economictimes.indiatimes.com/", "site": "Economic Times"},
                {"url": "https://www.business-standard.com/", "site": "Business Standard"},
                {"url": "https://www.livemint.com/", "site": "Mint"}
            ],
            "technology": [
                {"url": "https://tech.hindustantimes.com/", "site": "HT Tech"},
                {"url": "https://www.gadgetsnow.com/", "site": "Gadgets Now"},
                {"url": "https://www.digit.in/", "site": "Digit"}
            ],
            "sports": [
                {"url": "https://sportstar.thehindu.com/", "site": "Sportstar"},
                {"url": "https://www.espn.in/", "site": "ESPN India"},
                {"url": "https://timesofindia.indiatimes.com/sports", "site": "TOI Sports"}
            ],
            "entertainment": [
                {"url": "https://timesofindia.indiatimes.com/entertainment", "site": "TOI Entertainment"},
                {"url": "https://www.filmfare.com/", "site": "Filmfare"},
                {"url": "https://indianexpress.com/section/entertainment/", "site": "IE Entertainment"}
            ],
            "politics": [
                {"url": "https://www.ndtv.com/india", "site": "NDTV India"},
                {"url": "https://www.news18.com/politics/", "site": "News18 Politics"},
                {"url": "https://www.thehindu.com/news/national/", "site": "The Hindu National"}
            ]
        }
        # User agent list to rotate and avoid being blocked
        self.user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36'
        ]
        
    def connect_to_db(self):
        """Establish connection to the MySQL database."""
        try:
            conn = mysql.connector.connect(**self.db_config)
            return conn
        except mysql.connector.Error as err:
            print(f"Database connection failed: {err}")
            return None
            
    def create_tables_if_not_exist(self):
        """Create necessary database tables if they don't exist."""
        conn = self.connect_to_db()
        if not conn:
            return False
            
        cursor = conn.cursor()
        try:
            # Create table for news articles with improved schema
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS news_articles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    url VARCHAR(500) NOT NULL UNIQUE,
                    image_url VARCHAR(500),
                    summary TEXT,
                    category VARCHAR(50) NOT NULL,
                    source VARCHAR(100) NOT NULL,
                    published_date DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    language VARCHAR(50) DEFAULT 'english',
                    author VARCHAR(100),
                    content_snippet TEXT,
                    is_featured BOOLEAN DEFAULT FALSE,
                    view_count INT DEFAULT 0,
                    INDEX idx_category (category),
                    INDEX idx_source (source),
                    INDEX idx_created_at (created_at),
                    FULLTEXT INDEX ft_title_summary (title, summary)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ''')
            conn.commit()
            return True
        except mysql.connector.Error as err:
            print(f"Error creating tables: {err}")
            return False
        finally:
            cursor.close()
            conn.close()
    
    def scrape_the_hindu(self, article_element, category):
        """Extract article data from The Hindu."""
        try:
            title_element = article_element.find(['h1', 'h2', 'h3'], class_=lambda c: c and ('title' in c or 'headline' in c))
            if not title_element:
                title_element = article_element.find(['h1', 'h2', 'h3'])
            if not title_element:
                return None
                
            title = title_element.text.strip()
            
            link_element = title_element.find('a') if title_element.name != 'a' else title_element
            if not link_element:
                link_element = article_element.find('a', href=True)
            if not link_element or not link_element.get('href'):
                return None
                
            url = link_element['href']
            if not url.startswith('http'):
                url = urljoin("https://www.thehindu.com/", url)
                
            image_element = article_element.find('img')
            image_url = None
            if image_element:
                image_url = image_element.get('src') or image_element.get('data-src')
                if image_url and not image_url.startswith('http'):
                    image_url = urljoin("https://www.thehindu.com/", image_url)
                
            summary_element = article_element.find('p')
            summary = summary_element.text.strip() if summary_element else None
            
            # Try to find author if available
            author_element = article_element.find(['span', 'div'], class_=lambda c: c and ('author' in c.lower() or 'byline' in c.lower()))
            author = author_element.text.strip() if author_element else None
            
            return {
                "title": title,
                "url": url,
                "image_url": image_url,
                "summary": summary,
                "category": category,
                "source": "The Hindu",
                "published_date": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                "author": author,
                "language": "english"
            }
        except Exception as e:
            print(f"Error scraping The Hindu article: {e}")
            return None
    
    def scrape_times_of_india(self, article_element, category):
        """Extract article data from Times of India."""
        try:
            title_element = article_element.find(class_=lambda c: c and ('title' in c or 'headline' in c))
            if not title_element:
                title_element = article_element.find(['h1', 'h2', 'h3'])
            if not title_element:
                return None
                
            title = title_element.text.strip()
            
            link_element = title_element.find('a') if title_element.name != 'a' else title_element
            if not link_element:
                link_element = article_element.find('a', href=True)
            if not link_element or not link_element.get('href'):
                return None
                
            url = link_element['href']
            if not url.startswith('http'):
                url = urljoin("https://timesofindia.indiatimes.com/", url)
                
            image_element = article_element.find('img')
            image_url = None
            if image_element:
                image_url = image_element.get('src') or image_element.get('data-src')
                
            summary_element = article_element.find('p')
            summary = summary_element.text.strip() if summary_element else None
            
            return {
                "title": title,
                "url": url,
                "image_url": image_url,
                "summary": summary,
                "category": category,
                "source": "Times of India",
                "published_date": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                "language": "english"
            }
        except Exception as e:
            print(f"Error scraping Times of India article: {e}")
            return None
            
    def scrape_generic(self, article_element, source_info, category):
        """Extract article data using a more generic approach for Indian news sites."""
        try:
            # Look for common patterns in Indian news sites
            title_element = (
                article_element.find(['h1', 'h2', 'h3', 'h4'], class_=lambda c: c and ('title' in str(c).lower() or 'headline' in str(c).lower())) or 
                article_element.find(['h1', 'h2', 'h3', 'h4']) or
                article_element.find('a', class_=lambda c: c and ('title' in str(c).lower() or 'headline' in str(c).lower()))
            )
            
            if not title_element:
                return None
                
            title = title_element.text.strip()
            
            link_element = title_element if title_element.name == 'a' else article_element.find('a', href=True)
            if not link_element or not link_element.get('href'):
                return None
                
            url = link_element['href']
            if not url.startswith('http'):
                url = urljoin(source_info["url"], url)
                
            image_element = article_element.find('img')
            image_url = None
            if image_element:
                image_url = image_element.get('src') or image_element.get('data-src') or image_element.get('data-original')
                if image_url and not image_url.startswith('http'):
                    image_url = urljoin(source_info["url"], image_url)
                
            summary_element = article_element.find('p')
            summary = summary_element.text.strip() if summary_element else None
            
            # Try to find author and date if available
            author_element = article_element.find(['span', 'div', 'a'], class_=lambda c: c and ('author' in str(c).lower() or 'byline' in str(c).lower()))
            author = author_element.text.strip() if author_element else None
            
            date_element = article_element.find(['span', 'div', 'time'], class_=lambda c: c and ('date' in str(c).lower() or 'time' in str(c).lower() or 'published' in str(c).lower()))
            published_date = date_element.text.strip() if date_element else datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            if not isinstance(published_date, str) or len(published_date) < 5:
                published_date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                
            return {
                "title": title,
                "url": url,
                "image_url": image_url,
                "summary": summary,
                "category": category,
                "source": source_info["site"],
                "published_date": published_date,
                "author": author,
                "language": "english"
            }
        except Exception as e:
            print(f"Error scraping article from {source_info['site']}: {e}")
            return None
    
    def get_article_content(self, url, source_name):
        """Get more detailed content from the article page."""
        try:
            headers = {
                'User-Agent': random.choice(self.user_agents),
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.5',
                'Referer': 'https://www.google.com/',
                'Connection': 'keep-alive',
                'Upgrade-Insecure-Requests': '1',
                'Cache-Control': 'max-age=0'
            }
            
            response = requests.get(url, headers=headers, timeout=15)
            if response.status_code != 200:
                return None
                
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Different sites have content in different structures
            content_elements = soup.find(['div', 'article'], class_=lambda c: c and ('content' in str(c).lower() or 'article-body' in str(c).lower() or 'story-content' in str(c).lower()))
            
            if content_elements:
                paragraphs = content_elements.find_all('p')
                if paragraphs:
                    # Get first few paragraphs as a preview
                    content = ' '.join([p.text.strip() for p in paragraphs[:3]])
                    return content[:500] + ('...' if len(content) > 500 else '')
            
            return None
        except Exception as e:
            print(f"Error getting article content from {source_name}: {e}")
            return None
            
    def scrape_news(self):
        """Scrape news from all configured sources."""
        all_articles = []
        
        for category, sources in self.sources.items():
            print(f"Scraping {category} news...")
            
            for source_info in sources:
                try:
                    print(f"  From {source_info['site']}...")
                    
                    headers = {
                        'User-Agent': random.choice(self.user_agents),
                        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language': 'en-US,en;q=0.5',
                        'Referer': 'https://www.google.com/',
                        'Connection': 'keep-alive'
                    }
                    
                    response = requests.get(source_info['url'], headers=headers, timeout=15)
                    if response.status_code != 200:
                        print(f"  Failed to fetch {source_info['site']}: {response.status_code}")
                        continue
                        
                    soup = BeautifulSoup(response.content, 'html.parser')
                    
                    # Different sites have different structures, try common patterns for Indian news sites
                    article_elements = (
                        soup.find_all('article') or 
                        soup.find_all('div', class_=lambda c: c and any(term in str(c).lower() for term in ['article', 'story', 'news-item', 'card', 'listing', 'entry'])) or
                        soup.select('.article, .story, .news-item, .card, .listing')
                    )
                    
                    if not article_elements:
                        # Fallback to find elements that likely contain articles
                        article_elements = soup.find_all(['div', 'li', 'section'], class_=lambda c: c and any(term in str(c).lower() for term in ['article', 'story', 'news', 'post', 'entry', 'card', 'item']))
                    
                    articles_from_source = []
                    for article_element in article_elements[:15]:  # Limit to 15 articles per source
                        if source_info['site'] == 'The Hindu':
                            article_data = self.scrape_the_hindu(article_element, category)
                        elif source_info['site'] == 'Times of India' or source_info['site'].startswith('TOI'):
                            article_data = self.scrape_times_of_india(article_element, category)
                        else:
                            article_data = self.scrape_generic(article_element, source_info, category)
                            
                        if article_data and len(article_data['title']) > 10:  # Filter out items with short titles
                            # Try to get more content for some articles
                            if len(articles_from_source) < 5:  # Limit detailed scraping to save time
                                content_snippet = self.get_article_content(article_data['url'], source_info['site'])
                                if content_snippet:
                                    article_data['content_snippet'] = content_snippet
                            
                            # Make some articles featured
                            article_data['is_featured'] = random.random() < 0.2  # 20% chance of being featured
                            
                            articles_from_source.append(article_data)
                            
                    all_articles.extend(articles_from_source)
                    print(f"  Scraped {len(articles_from_source)} articles from {source_info['site']}")
                    time.sleep(2)  # Be polite to the server and avoid rate limiting
                    
                except Exception as e:
                    print(f"Error scraping {source_info['site']}: {e}")
                    
        return all_articles
                
    def save_to_db(self, articles):
        """Save scraped articles to the database."""
        if not articles:
            print("No articles to save.")
            return 0
            
        conn = self.connect_to_db()
        if not conn:
            return 0
            
        cursor = conn.cursor()
        articles_saved = 0
        
        try:
            for article in articles:
                try:
                    # Handle optional fields
                    author = article.get('author')
                    content_snippet = article.get('content_snippet')
                    is_featured = article.get('is_featured', False)
                    language = article.get('language', 'english')
                    
                    # Insert the article, handling duplicates
                    cursor.execute('''
                        INSERT IGNORE INTO news_articles 
                        (title, url, image_url, summary, category, source, published_date, author, content_snippet, is_featured, language)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ''', (
                        article['title'],
                        article['url'],
                        article['image_url'],
                        article['summary'],
                        article['category'],
                        article['source'],
                        article['published_date'],
                        author,
                        content_snippet,
                        is_featured,
                        language
                    ))
                    
                    if cursor.rowcount > 0:
                        articles_saved += 1
                        
                except mysql.connector.Error as err:
                    print(f"Error saving article: {err}")
                    
            conn.commit()
            print(f"Saved {articles_saved} new articles to database.")
            return articles_saved
            
        except mysql.connector.Error as err:
            print(f"Database error: {err}")
            return 0
        finally:
            cursor.close()
            conn.close()
    
    def get_latest_articles(self, limit=20, category=None):
        """Retrieve latest articles from the database."""
        conn = self.connect_to_db()
        if not conn:
            return []
            
        cursor = conn.cursor(dictionary=True)
        articles = []
        
        try:
            query = '''
                SELECT id, title, url, image_url, summary, category, source, 
                       published_date, author, content_snippet, is_featured
                FROM news_articles
            '''
            
            params = []
            if category:
                query += ' WHERE category = %s'
                params.append(category)
                
            query += ' ORDER BY created_at DESC LIMIT %s'
            params.append(limit)
            
            cursor.execute(query, params)
            articles = cursor.fetchall()
            
        except mysql.connector.Error as err:
            print(f"Error retrieving articles: {err}")
        finally:
            cursor.close()
            conn.close()
            
        return articles
    
    def search_articles(self, query, limit=20):
        """Search for articles by keyword."""
        conn = self.connect_to_db()
        if not conn:
            return []
            
        cursor = conn.cursor(dictionary=True)
        articles = []
        
        try:
            # Use MySQL FULLTEXT search
            search_query = '''
                SELECT id, title, url, image_url, summary, category, source, 
                       published_date, author, content_snippet, is_featured,
                       MATCH(title, summary) AGAINST(%s IN NATURAL LANGUAGE MODE) AS relevance
                FROM news_articles
                WHERE MATCH(title, summary) AGAINST(%s IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT %s
            '''
            
            cursor.execute(search_query, (query, query, limit))
            articles = cursor.fetchall()
            
        except mysql.connector.Error as err:
            print(f"Error searching articles: {err}")
            
            # Fallback to simpler LIKE search if FULLTEXT search fails
            try:
                like_query = '''
                    SELECT id, title, url, image_url, summary, category, source, 
                           published_date, author, content_snippet, is_featured
                    FROM news_articles
                    WHERE title LIKE %s OR summary LIKE %s
                    ORDER BY created_at DESC
                    LIMIT %s
                '''
                
                search_term = f"%{query}%"
                cursor.execute(like_query, (search_term, search_term, limit))
                articles = cursor.fetchall()
                
            except mysql.connector.Error as err2:
                print(f"Error with fallback search: {err2}")
        finally:
            cursor.close()
            conn.close()
            
        return articles
            
    def run(self):
        """Run the full news aggregation process."""
        print("Starting news aggregation...")
        self.create_tables_if_not_exist()
        articles = self.scrape_news()
        articles_saved = self.save_to_db(articles)
        print(f"News aggregation completed. Total new articles: {articles_saved}")
        return articles_saved

# Example usage
if __name__ == "__main__":
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'your_password',  # Change to your actual password
        'database': 'news_aggregator'  # Change to your database name
    }
    
    aggregator = NewsAggregator(db_config)
    aggregator.run()