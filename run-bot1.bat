@echo off
SET AI_NAME=bot1
explorer "http://10.11.8.92:8001/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.92:8001 -t .\www\public_html