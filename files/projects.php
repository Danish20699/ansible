<?php
$conn = pg_connect("host=localhost dbname=portfolio_db user=portfolio_user password=danish1p");
if (!$conn) { die("Connection failed!"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Experience - Danish Nazir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; }
        .nav { background: rgba(0,0,0,0.3); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; font-size: 22px; font-weight: bold; }
        .nav a { color: rgba(255,255,255,0.9); text-decoration: none; margin-left: 30px; font-weight: 500; }
        .nav a:hover { color: #4fc3f7; }
        .content { padding: 60px 40px; max-width: 800px; margin: auto; }
        .content h1 { color: white; font-size: 40px; margin-bottom: 40px; text-align: center; }
        .exp-card { background: rgba(255,255,255,0.1); padding: 30px; border-radius: 12px; border-left: 4px solid #4fc3f7; margin-bottom: 25px; }
        .exp-title { color: white; font-size: 22px; font-weight: bold; margin-bottom: 8px; }
        .exp-duration { color: #4fc3f7; font-size: 14px; margin-bottom: 15px; font-weight: 600; }
        .exp-desc { color: rgba(255,255,255,0.85); line-height: 1.6; }
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
    <div class="content">
        <h1>My Experience</h1>
        <?php
        $result = pg_query($conn, "SELECT * FROM projects ORDER BY id DESC");
        while ($row = pg_fetch_assoc($result)) {
            echo "<div class=\"exp-card\">";
            echo "<div class=\"exp-title\">" . $row["title"] . "</div>";
            echo "<div class=\"exp-duration\">" . $row["duration"] . "</div>";
            echo "<div class=\"exp-desc\">" . $row["description"] . "</div>";
            echo "</div>";
        }
        pg_close($conn);
        ?>
    </div>
</body>
</html>
