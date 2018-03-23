@echo off
SET AI_NAME=bot_tin2
explorer "http://10.11.8.157:8002/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.157:8002 -t .\www\public_html