@echo off
SET AI_NAME=bot2
explorer "http://10.11.8.92:8002/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.92:8002 -t .\www\public_html