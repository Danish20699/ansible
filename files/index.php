<?php
// Read DB credentials from environment variables (set by Apache envvars).
// Falls back to sensible defaults if a variable is missing.
$host = getenv('PGHOST') ?: 'localhost';
$db   = getenv('PGDATABASE') ?: 'portfolio_db';
$user = getenv('PGUSER') ?: 'portfolio_user';
$pass = getenv('PGPASSWORD') ?: '';
$port = getenv('PGPORT') ?: '5432';

$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass");
if (!$conn) { die("Connection failed!"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Danish Nazir - DevOps Portfolio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }
        .nav {
            background: rgba(0,0,0,0.3);
            padding: 20px 40px;
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-brand { color: white; font-size: 22px; font-weight: bold; }
        .nav a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav a:hover { color: #4fc3f7; }
        .hero {
            text-align: center;
            padding: 100px 20px;
            color: white;
        }
        .hero h1 {
            font-size: 56px;
            margin-bottom: 10px;
            background: linear-gradient(to right, #fff, #4fc3f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero h3 {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 30px;
            color: #4fc3f7;
        }
        .hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto 15px;
            line-height: 1.6;
            color: rgba(255,255,255,0.85);
        }
        .cta-buttons { margin-top: 40px; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            background: #4fc3f7;
            color: #1e3c72;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn:hover { transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-brand">DN.</div>
        <div>
            <a href="index.php">Home</a>
            <a href="skills.php">Skills</a>
            <a href="projects.php">Experience</a>
            <a href="education.php">Education</a>
        </div>
    </div>
    <div class="hero">
        <h1>Danish Nazir</h1>
        <h3>AI/ML &amp; DevOps Engineer</h3>
        <p>Passionate about DevOps, AI/ML, and building scalable systems. Currently working on Generative AI solutions and cloud-native infrastructure.</p>
        <div class="cta-buttons">
            <a href="skills.php" class="btn">View Skills</a>
            <a href="projects.php" class="btn">View Experience</a>
        </div>
    </div>
</body>
</html>
