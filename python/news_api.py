from flask import Flask, jsonify, request
import mysql.connector
from news_aggregator import NewsAggregator
import os
import logging  
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    filename='news_api.log'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'db'
}

def connect_to_db():
    """Establish connection to the MySQL database."""
    try:
        conn = mysql.connector.connect(**db_config)
        return conn
    except mysql.connector.Error as err:
        logger.error(f"Database connection failed: {err}")
        return None

@app.route('/api/news', methods=['GET'])
def get_news():
    """API endpoint to get news articles with optional filtering."""
    try:
        category = request.args.get('category', default=None)
        source = request.args.get('source', default=None)
        limit = request.args.get('limit', default=20, type=int)
        offset = request.args.get('offset', default=0, type=int)
        
        conn = connect_to_db()
        if not conn:
            return jsonify({"error": "Database connection failed"}), 500
            
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT * FROM news_articles WHERE 1=1"
        params = []
        
        if category:
            query += " AND category = %s"
            params.append(category)
            
        if source:
            query += " AND source = %s"
            params.append(source)
            
        query += " ORDER BY published_date DESC LIMIT %s OFFSET %s"
        params.extend([limit, offset])
        
        cursor.execute(query, params)
        articles = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return jsonify({"articles": articles})
        
    except Exception as e:
        logger.error(f"Error in get_news: {e}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/categories', methods=['GET'])
def get_categories():
    """API endpoint to get all available news categories."""
    try:
        conn = connect_to_db()
        if not conn:
            return jsonify({"error": "Database connection failed"}), 500
            
        cursor = conn.cursor()
        
        cursor.execute("SELECT DISTINCT category FROM news_articles ORDER BY category")
        categories = [row[0] for row in cursor.fetchall()]
        
        cursor.close()
        conn.close()
        
        return jsonify({"categories": categories})
        
    except Exception as e:
        logger.error(f"Error in get_categories: {e}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/sources', methods=['GET'])
def get_sources():
    """API endpoint to get all news sources."""
    try:
        category = request.args.get('category', default=None)
        
        conn = connect_to_db()
        if not conn:
            return jsonify({"error": "Database connection failed"}), 500
            
        cursor = conn.cursor()
        
        query = "SELECT DISTINCT source FROM news_articles"
        params = []
        
        if category:
            query += " WHERE category = %s"
            params.append(category)
            
        query += " ORDER BY source"
        
        cursor.execute(query, params)
        sources = [row[0] for row in cursor.fetchall()]
        
        cursor.close()
        conn.close()
        
        return jsonify({"sources": sources})
        
    except Exception as e:
        logger.error(f"Error in get_sources: {e}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/refresh', methods=['POST'])
def refresh_news():
    """API endpoint to manually trigger news aggregation."""
    try:
        auth_key = request.headers.get('X-API-Key')
        
        # Simple API key validation (should use a more secure method in production)
        if auth_key != 'your_secret_api_key':
            return jsonify({"error": "Unauthorized"}), 401
            
        aggregator = NewsAggregator(db_config)
        articles_saved = aggregator.run()
        
        return jsonify({
            "success": True,
            "message": f"News aggregation completed. Added {articles_saved} new articles."
        })
        
    except Exception as e:
        logger.error(f"Error in refresh_news: {e}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/status', methods=['GET'])
def get_status():
    """API endpoint to check the status of the news service."""
    try:
        conn = connect_to_db()
        if not conn:
            return jsonify({
                "status": "error",
                "database": "disconnected",
                "message": "Database connection failed"
            }), 500
            
        cursor = conn.cursor()
        
        # Check the latest article
        cursor.execute("SELECT MAX(created_at) FROM news_articles")
        latest = cursor.fetchone()[0]
        
        # Count total articles
        cursor.execute("SELECT COUNT(*) FROM news_articles")
        count = cursor.fetchone()[0]
        
        # Count articles by category
        cursor.execute("SELECT category, COUNT(*) FROM news_articles GROUP BY category")
        categories = {row[0]: row[1] for row in cursor.fetchall()}
        
        cursor.close()
        conn.close()
        
        status_data = {
            "status": "ok",
            "database": "connected",
            "articles_count": count,
            "latest_article": latest.strftime('%Y-%m-%d %H:%M:%S') if latest else None,
            "categories": categories
        }
        
        return jsonify(status_data)
        
    except Exception as e:
        logger.error(f"Error in get_status: {e}")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

if __name__ == '__main__':
    # Create a scheduler to periodically update news
    import threading
    import time
    
    def scheduled_news_update():
        """Function to periodically update news articles."""
        while True:
            try:
                logger.info("Running scheduled news update")
                aggregator = NewsAggregator(db_config)
                articles_saved = aggregator.run()
                logger.info(f"Scheduled update completed. Added {articles_saved} new articles.")
                
                # Run every 3 hours
                time.sleep(3 * 60 * 60)
                
            except Exception as e:
                logger.error(f"Error in scheduled update: {e}")
                time.sleep(60 * 10)  # Wait 10 minutes if there's an error
    
    # Start the scheduler in a separate thread
    scheduler_thread = threading.Thread(target=scheduled_news_update)
    scheduler_thread.daemon = True
    scheduler_thread.start()
    
    # Run the Flask app
    app.run(host='0.0.0.0', port=5000, debug=True)

@app.route('/api/article/<int:article_id>', methods=['GET'])
def get_article_by_id(article_id):
    """API endpoint to get a specific article by ID."""
    try:
        conn = connect_to_db()
        if not conn:
            return jsonify({"error": "Database connection failed"}), 500
            
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT * FROM news_articles WHERE id = %s"
        cursor.execute(query, (article_id,))
        article = cursor.fetchone()
        
        cursor.close()
        conn.close()
        
        if not article:
            return jsonify({"error": "Article not found"}), 404
            
        return jsonify({"article": article})
        
    except Exception as e:
        logger.error(f"Error in get_article_by_id: {e}")
        return jsonify({"error": str(e)}), 500