<?php
$conn = pg_connect("host=localhost dbname=portfolio_db user=portfolio_user password=danish1p");
if (!$conn) { die("Connection failed!"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Skills - Danish Nazir</title>
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
        }
        .nav a:hover { color: #4fc3f7; }
        .content { padding: 60px 40px; max-width: 1000px; margin: auto; }
        .content h1 { color: white; font-size: 40px; margin-bottom: 40px; text-align: center; }
        .skills-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
        }
        .skill-card { 
            background: rgba(255,255,255,0.1); 
            backdrop-filter: blur(10px);
            padding: 25px; 
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s;
        }
        .skill-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.15); }
        .skill-name { color: white; font-size: 20px; font-weight: bold; margin-bottom: 8px; }
        .skill-category { color: #4fc3f7; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
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
        <h1>My Skills</h1>
        <div class="skills-grid">
            <?php
            $result = pg_query($conn, "SELECT * FROM skills ORDER BY category");
            while ($row = pg_fetch_assoc($result)) {
                echo "<div class=\"skill-card\">";
                echo "<div class=\"skill-name\">" . $row["skill_name"] . "</div>";
                echo "<div class=\"skill-category\">" . $row["category"] . "</div>";
                echo "</div>";
            }
            pg_close($conn);
            ?>
        </div>
    </div>
</body>
</html>
