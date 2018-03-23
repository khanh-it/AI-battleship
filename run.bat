@echo off
SET AI_NAME=ea_team_no1
explorer "http://10.11.8.92:8000/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.92:8000 -t .\www\public_html