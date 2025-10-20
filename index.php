<?php
// =======================
// CONFIGURATION
// =======================
$host = "localhost";
$dbname = "dbdjcrwg8qxomf";
$username = "u3di7wla4eggh";
$password = "zmtyayn0i9br";
 
// =======================
// DATABASE CONNECTION
// =======================
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
session_start();
 
// =======================
// AUTO-CREATE TABLES
// =======================
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
 
$conn->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE
)");
 
$conn->query("CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    image_url VARCHAR(255),
    category_id INT,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
)");
 
// =======================
// ADD SAMPLE DATA
// =======================
$res = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
if ($res->fetch_assoc()['cnt'] == 0) {
    $sampleCategories = ['World', 'Sports', 'Technology', 'Entertainment'];
    foreach ($sampleCategories as $cat) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat);
        $stmt->execute();
    }
}
 
$res = $conn->query("SELECT COUNT(*) AS cnt FROM articles");
if ($res->fetch_assoc()['cnt'] == 0) {
    $conn->query("INSERT INTO articles (title, content, image_url, category_id, author_id) VALUES
    ('Breaking: Tech Innovation', 'A new technology has taken the world by storm...', 'https://via.placeholder.com/800x400', 3, NULL),
    ('Sports Championship 2025', 'An epic final match concluded with...', 'https://via.placeholder.com/800x400', 2, NULL)");
}
 
// =======================
// SIGNUP
// =======================
if (isset($_POST['signup'])) {
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $user, $pass);
    if ($stmt->execute()) {
        $_SESSION['user'] = $user;
    } else {
        $error = "Signup failed: username may already exist.";
    }
}
 
// =======================
// LOGIN
// =======================
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && password_verify($pass, $result['password'])) {
        $_SESSION['user'] = $user;
    } else {
        $error = "Invalid credentials.";
    }
}
 
// =======================
// LOGOUT
// =======================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
 
// =======================
// ADD ARTICLE
// =======================
if (isset($_POST['add_article']) && isset($_SESSION['user'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_POST['image_url'];
    $cat_id = $_POST['category'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->bind_param("s", $_SESSION['user']);
    $stmt->execute();
    $uid = $stmt->get_result()->fetch_assoc()['id'];
    $stmt = $conn->prepare("INSERT INTO articles (title, content, image_url, category_id, author_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $title, $content, $image, $cat_id, $uid);
    $stmt->execute();
}
 
// =======================
// FETCH DATA
// =======================
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
$articles = $conn->query("SELECT a.*, c.name as category_name FROM articles a LEFT JOIN categories c ON a.category_id=c.id ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CNN Clone</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f4f4f4; }
        header { background:#cc0000; color:white; padding:10px 20px; display:flex; justify-content:space-between; align-items:center; }
        header h1 { margin:0; font-size:24px; }
        nav a { color:white; margin:0 10px; text-decoration:none; }
        .container { max-width:1200px; margin:auto; padding:20px; }
        .articles { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:20px; }
        .article { background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
        .article img { width:100%; }
        .article h3 { margin:10px; }
        form { background:white; padding:20px; margin-bottom:20px; border-radius:8px; }
        input, textarea, select { width:100%; padding:10px; margin:5px 0; }
        button { background:#cc0000; color:white; padding:10px 15px; border:none; cursor:pointer; }
        button:hover { background:#a00000; }
        .error { color:red; }
    </style>
</head>
<body>
<header>
    <h1>CNN Clone</h1>
    <nav>
        <?php if(isset($_SESSION['user'])): ?>
            Welcome, <?= $_SESSION['user'] ?> | 
            <a href="?logout=1">Logout</a>
        <?php else: ?>
            <a href="#login">Login</a> | 
            <a href="#signup">Signup</a>
        <?php endif; ?>
    </nav>
</header>
<div class="container">
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
 
    <?php if(!isset($_SESSION['user'])): ?>
        <form method="post" id="login">
            <h2>Login</h2>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="login">Login</button>
        </form>
        <form method="post" id="signup">
            <h2>Signup</h2>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="signup">Signup</button>
        </form>
    <?php else: ?>
        <form method="post">
            <h2>Add Article</h2>
            <input type="text" name="title" placeholder="Title" required>
            <textarea name="content" placeholder="Content" required></textarea>
            <input type="text" name="image_url" placeholder="Image URL">
            <select name="category" required>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button name="add_article">Add Article</button>
        </form>
    <?php endif; ?>
 
    <h2>Latest Articles</h2>
    <div class="articles">
        <?php foreach($articles as $art): ?>
            <div class="article">
                <img src="<?= $art['image_url'] ?>" alt="">
                <h3><?= $art['title'] ?></h3>
                <p><strong><?= $art['category_name'] ?></strong> - <?= substr($art['content'],0,100) ?>...</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
 
