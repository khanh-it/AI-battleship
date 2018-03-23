@echo off
SET AI_NAME=bot_tin1
explorer "http://10.11.8.157:8001/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.157:8001 -t .\www\public_html