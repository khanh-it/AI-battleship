@echo off
explorer "http://10.11.8.157:6789/game-engine.php"
.\php\php -c .\php\ -S 10.11.8.157:6789 -t .\www\public_html