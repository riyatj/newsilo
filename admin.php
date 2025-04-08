<?php
// Database connection
$host = 'localhost';
$dbname = 'db';
$username = 'root'; // Change this to your actual database username
$password = ''; // Change this to your actual database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Authentication check
session_start();

// Hardcoded admin credentials
$admin_username = "admin";
$admin_password = "admin123";

// Simple login functionality
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Check hardcoded admin credentials first
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['user_id'] = 0; // Special ID for hardcoded admin
        $_SESSION['username'] = $admin_username;
    } else {
        // Try database authentication as fallback
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
        } else {
            $login_error = "Incorrect username or password";
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// CRUD Operations
$table = isset($_GET['table']) ? $_GET['table'] : 'news_articles';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    try {
        if ($action === 'add') {
            $columns = array_keys($_POST);
            $values = array_values($_POST);
            
            // Remove the 'save' element
            $saveIndex = array_search('save', $columns);
            if ($saveIndex !== false) {
                unset($columns[$saveIndex]);
                unset($values[$saveIndex]);
            }
            
            $sql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES (" . str_repeat("?, ", count($columns) - 1) . "?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
        } elseif ($action === 'edit' && $id) {
            $setClause = "";
            $values = [];
            
            foreach ($_POST as $column => $value) {
                if ($column != 'save') {
                    $setClause .= "$column = ?, ";
                    $values[] = $value;
                }
            }
            
            $setClause = rtrim($setClause, ", ");
            $values[] = $id;
            
            $sql = "UPDATE $table SET $setClause WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?table=$table");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Delete operation
if ($action === 'delete' && $id) {
    try {
        $sql = "DELETE FROM $table WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?table=$table");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get table structure
function getTableColumns($pdo, $table) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM $table");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get record by ID
function getRecordById($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get dashboard stats
function getDashboardStats($pdo) {
    $stats = [];
    
    // Count news articles
    $stmt = $pdo->query("SELECT COUNT(*) FROM news_articles");
    $stats['news_count'] = $stmt->fetchColumn();
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['users_count'] = $stmt->fetchColumn();
    
    // Count contact messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
    $stats['messages_count'] = $stmt->fetchColumn();
    
    // Get popular categories
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM news_articles GROUP BY category ORDER BY count DESC LIMIT 5");
    $stats['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent articles
    $stmt = $pdo->query("SELECT id, title, created_at FROM news_articles ORDER BY created_at DESC LIMIT 5");
    $stats['recent_articles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsilo Admin Panel</title>
    <style>
        /* Black and White Theme */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            color: #333;
        }
        header {
            background-color: #000;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            display: flex;
            min-height: calc(100vh - 56px);
        }
        .sidebar {
            width: 200px;
            background-color: #222;
            color: #fff;
            padding: 20px 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            padding: 10px 20px;
        }
        .sidebar li:hover {
            background-color: #444;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            width: 100%;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #222;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #222;
            color: #fff;
        }
        .btn-danger {
            background-color: #ff3333;
            color: #fff;
        }
        .btn-edit {
            background-color: #555;
            color: #fff;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .card h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
        }
        .error {
            color: #ff3333;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="login-form">
            <h2>Newsilo Admin Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Login</button>
            </form>
        </div>
    <?php else: ?>
        <header>
            <div class="logo">
            <img src="C:\xampp\htdocs\newsilo\images\LOGO.PNG" alt="">Newsilo Admin</div>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="?logout=1" class="btn btn-danger">Logout</a>
            </div>
        </header>
        <div class="container">
            <div class="sidebar">
                <ul>
                    <li><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Dashboard</a></li>
                    <li><a href="?table=news_articles">News Articles</a></li>
                    <li><a href="?table=users">Users</a></li>
                    <li><a href="?table=contact_messages">Contact Messages</a></li>
                    <li><a href="welcome.php">Newsilo</a></li>
                </ul>
            </div>
            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!isset($_GET['table']) && !isset($_GET['action'])): ?>
                    <?php $stats = getDashboardStats($pdo); ?>
                    <h1>Dashboard</h1>
                    
                    <div class="dashboard-cards">
                        <div class="card">
                            <h3>News Articles</h3>
                            <p>Total: <?php echo $stats['news_count']; ?></p>
                            <a href="?table=news_articles" class="btn btn-primary">Manage</a>
                        </div>
                        <div class="card">
                            <h3>Users</h3>
                            <p>Total: <?php echo $stats['users_count']; ?></p>
                            <a href="?table=users" class="btn btn-primary">Manage</a>
                        </div>
                        <div class="card">
                            <h3>Contact Messages</h3>
                            <p>Total: <?php echo $stats['messages_count']; ?></p>
                            <a href="?table=contact_messages" class="btn btn-primary">Manage</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>Recent News Articles</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_articles'] as $article): ?>
                                <tr>
                                    <td><?php echo $article['id']; ?></td>
                                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                                    <td><?php echo $article['created_at']; ?></td>
                                    <td>
                                        <a href="?table=news_articles&action=edit&id=<?php echo $article['id']; ?>" class="btn btn-edit">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card">
                        <h3>Popular Categories</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Articles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['categories'] as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category']); ?></td>
                                    <td><?php echo $category['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                
                <?php elseif ($action === 'list'): ?>
                    <h1>Manage <?php echo ucfirst($table); ?></h1>
                    
                    <?php if ($table !== 'contact_messages'): ?>
                    <a href="?table=<?php echo $table; ?>&action=add" class="btn btn-primary">Add New</a>
                    <?php endif; ?>
                    
                    <?php
                    // Get total records
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                    $total_records = $stmt->fetchColumn();
                    
                    // Pagination
                    $records_per_page = 10;
                    $total_pages = ceil($total_records / $records_per_page);
                    
                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($current_page - 1) * $records_per_page;
                    
                    $sql = "SELECT * FROM $table ORDER BY id DESC LIMIT $offset, $records_per_page";
                    $stmt = $pdo->query($sql);
                    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($records) > 0):
                    ?>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($records[0]) as $column): ?>
                                <th><?php echo $column; ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <?php foreach ($record as $value): ?>
                                <td><?php echo htmlspecialchars(substr($value, 0, 100)); ?><?php echo strlen($value) > 100 ? '...' : ''; ?></td>
                                <?php endforeach; ?>
                                <td class="actions">
                                    <?php if ($table === 'news_articles'): ?>
                                        <a href="?table=<?php echo $table; ?>&action=edit&id=<?php echo $record['id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="?table=<?php echo $table; ?>&action=delete&id=<?php echo $record['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                    <?php else: ?>
                                        <a href="?table=<?php echo $table; ?>&action=delete&id=<?php echo $record['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?table=<?php echo $table; ?>&page=<?php echo $i; ?>" class="btn <?php echo $i === $current_page ? 'btn-primary' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php else: ?>
                    <p>No records found.</p>
                    <?php endif; ?>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <h1><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> <?php echo ucfirst(substr($table, 0, -1)); ?></h1>
                    
                    <?php
                    $columns = getTableColumns($pdo, $table);
                    $record = $action === 'edit' ? getRecordById($pdo, $table, $id) : [];
                    ?>
                    
                    <form method="post">
                        <?php foreach ($columns as $column): ?>
                            <?php if ($column['Field'] !== 'id' && $column['Field'] !== 'created_at'): ?>
                                <div class="form-group">
                                    <label for="<?php echo $column['Field']; ?>"><?php echo ucfirst(str_replace('_', ' ', $column['Field'])); ?></label>
                                    
                                    <?php if (strpos($column['Type'], 'text') !== false): ?>
                                        <textarea id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" rows="5"><?php echo isset($record[$column['Field']]) ? htmlspecialchars($record[$column['Field']]) : ''; ?></textarea>
                                    
                                    <?php elseif ($column['Field'] === 'category'): ?>
                                        <select id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>">
                                            <option value="">Select Category</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT DISTINCT category FROM news_articles ORDER BY category");
                                            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                            foreach ($categories as $category): 
                                            ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo isset($record[$column['Field']]) && $record[$column['Field']] === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($category); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($column['Field'] === 'source'): ?>
                                        <select id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>">
                                            <option value="">Select Source</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT DISTINCT source FROM news_articles ORDER BY source");
                                            $sources = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                            foreach ($sources as $source): 
                                            ?>
                                            <option value="<?php echo htmlspecialchars($source); ?>" <?php echo isset($record[$column['Field']]) && $record[$column['Field']] === $source ? 'selected' : ''; ?>><?php echo htmlspecialchars($source); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($column['Field'] === 'is_featured'): ?>
                                        <select id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>">
                                            <option value="0" <?php echo isset($record[$column['Field']]) && $record[$column['Field']] == 0 ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo isset($record[$column['Field']]) && $record[$column['Field']] == 1 ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    
                                    <?php elseif ($column['Field'] === 'password_hash' && $action === 'add'): ?>
                                        <input type="password" id="password" name="password" required>
                                        <input type="hidden" name="<?php echo $column['Field']; ?>" value="<?php echo password_hash('password', PASSWORD_DEFAULT); ?>">
                                    
                                    <?php elseif ($column['Field'] === 'password'): ?>
                                        <input type="password" id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($column['Field'] === 'email'): ?>
                                        <input type="email" id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" value="<?php echo isset($record[$column['Field']]) ? htmlspecialchars($record[$column['Field']]) : ''; ?>" required>
                                    
                                    <?php elseif (strpos($column['Type'], 'date') !== false || strpos($column['Type'], 'time') !== false): ?>
                                        <input type="datetime-local" id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" value="<?php echo isset($record[$column['Field']]) ? date('Y-m-d\TH:i', strtotime($record[$column['Field']])) : ''; ?>">
                                    
                                    <?php else: ?>
                                        <input type="text" id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" value="<?php echo isset($record[$column['Field']]) ? htmlspecialchars($record[$column['Field']]) : ''; ?>" <?php echo $column['Null'] === 'NO' ? 'required' : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                        <a href="?table=<?php echo $table; ?>" class="btn">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>