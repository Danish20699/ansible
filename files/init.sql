
-- init.sql
-- Portfolio database schema, data, and permissions.
-- Idempotent: safe to run multiple times (drops tables first).
-- This runs AFTER the database and user are created by setup_portfolio.sh.
-- Usage: sudo -u postgres psql -d portfolio_db -f init.sql

-- ===== Schema permissions for portfolio_user =====
-- database-level GRANT alone is NOT enough; the user also needs
-- USAGE + CREATE on the schema, and table/sequence grants below.
GRANT USAGE ON SCHEMA public TO portfolio_user;
GRANT CREATE ON SCHEMA public TO portfolio_user;

-- ===== Clean slate: drop existing tables so re-runs work cleanly =====
DROP TABLE IF EXISTS skills CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
DROP TABLE IF EXISTS education CASCADE;

-- ===== Create Tables =====

CREATE TABLE skills (
    id SERIAL PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    category VARCHAR(50)
);

CREATE TABLE projects (
    id SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    duration VARCHAR(50)
);

CREATE TABLE education (
    id SERIAL PRIMARY KEY,
    degree VARCHAR(150) NOT NULL,
    institution VARCHAR(150),
    duration VARCHAR(50)
);

-- ===== Insert Skills Data =====

INSERT INTO skills (skill_name, category) VALUES
('Linux', 'DevOps'),
('Git & GitHub', 'DevOps'),
('Docker', 'DevOps'),
('PostgreSQL', 'Database'),
('PHP', 'Backend'),
('Python', 'Programming'),
('React.js', 'Frontend'),
('JavaScript', 'Frontend'),
('Apache', 'DevOps'),
('AI/ML & Generative AI', 'AI/ML');

-- ===== Insert Experience Data =====

INSERT INTO projects (title, description, duration) VALUES
('DevOps Engineer - VervenTech', 'Working on AI/ML models and Generative AI solutions. Assisting in DevOps workflows using Docker and CI/CD.', 'Jun 2026 - Present'),
('Full Stack Engineer - QuantaFONS', 'Code Development & Maintenance. Producing clean, efficient, and well-documented code based on specifications.', 'Apr 2025 - Present'),
('Linux Labs - 22 DevOps Fundamentals', 'Hands-on practice covering Linux, Networking, Git, Virtualization, Databases, and Web Development.', '2026');

-- ===== Insert Education Data =====

INSERT INTO education (degree, institution, duration) VALUES
('BCA - Bachelor of Computer Applications', 'Islamia College of Science & Commerce, Srinagar', '2023 - 2026');

-- ===== Grant Table Permissions to portfolio_user =====

GRANT ALL PRIVILEGES ON TABLE skills TO portfolio_user;
GRANT ALL PRIVILEGES ON TABLE projects TO portfolio_user;
GRANT ALL PRIVILEGES ON TABLE education TO portfolio_user;

-- ===== Grant Sequence Permissions (needed for SERIAL auto-increment IDs) =====

GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO portfolio_user;

-- Setup Complete!
