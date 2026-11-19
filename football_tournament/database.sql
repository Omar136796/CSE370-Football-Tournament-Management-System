-- Database: football_tournament
CREATE DATABASE IF NOT EXISTS football_tournament;
USE football_tournament;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Teams table
CREATE TABLE teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    team_logo VARCHAR(255),
    founded_year INT,
    home_stadium VARCHAR(100),
    coach_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Players table
CREATE TABLE players (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT,
    player_name VARCHAR(100) NOT NULL,
    position VARCHAR(50),
    jersey_number INT,
    nationality VARCHAR(50),
    date_of_birth DATE,
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

-- Tournaments table
CREATE TABLE tournaments (
    tournament_id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_name VARCHAR(100) NOT NULL,
    start_date DATE,
    end_date DATE,
    tournament_type ENUM('league', 'knockout', 'group_stage') NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tournament Teams (many-to-many relationship)
CREATE TABLE tournament_teams (
    tournament_id INT,
    team_id INT,
    group_name VARCHAR(1) NULL, -- Only for group stage tournaments
    PRIMARY KEY (tournament_id, team_id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

-- Matches table
CREATE TABLE matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT,
    home_team_id INT,
    away_team_id INT,
    match_date DATETIME,
    venue VARCHAR(100),
    home_score INT DEFAULT NULL,
    away_score INT DEFAULT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'postponed') DEFAULT 'scheduled',
    match_round VARCHAR(50) NULL, -- For knockout stages (e.g., 'Quarterfinal', 'Semifinal')
    group_name VARCHAR(1) NULL, -- For group stage matches
    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (away_team_id) REFERENCES teams(team_id)
);

-- Match events (goals, assists, cards, etc.)
CREATE TABLE match_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT,
    player_id INT,
    team_id INT,
    event_type ENUM('goal', 'assist', 'yellow_card', 'red_card', 'substitution') NOT NULL,
    minute INT NOT NULL,
    related_player_id INT NULL, -- For assists or substitutions
    FOREIGN KEY (match_id) REFERENCES matches(match_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id),
    FOREIGN KEY (related_player_id) REFERENCES players(player_id)
);

-- Standings table (for league tournaments)
CREATE TABLE standings (
    standing_id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT,
    team_id INT,
    matches_played INT DEFAULT 0,
    wins INT DEFAULT 0,
    draws INT DEFAULT 0,
    losses INT DEFAULT 0,
    goals_for INT DEFAULT 0,
    goals_against INT DEFAULT 0,
    goal_difference INT DEFAULT 0,
    points INT DEFAULT 0,
    group_name VARCHAR(1) NULL, -- For group stage tournaments
    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    UNIQUE KEY (tournament_id, team_id, group_name)
);

-- User follows teams
CREATE TABLE user_follows (
    user_id INT,
    team_id INT,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

-- Match reviews/comments
CREATE TABLE match_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT,
    user_id INT,
    parent_review_id INT NULL, -- For threaded comments
    review_text TEXT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(match_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_review_id) REFERENCES match_reviews(review_id) ON DELETE SET NULL
);

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, email, user_type) 
VALUES ('admin', '$2y$10$nUTwg0bAfqBpsBUYqLpIhOFHL9n9xJPerGXd/cg3gSGRcFfsUQzuq', 'admin@example.com', 'admin');

-- Insert sample user (password: user123)
INSERT INTO users (username, password, email) 
VALUES ('user1', '$2y$10$PITPG9iKndIILZlEggs43ufB.f8bp3Mlgkvn4A6F44WVhm84kR4Gu', 'user1@example.com');