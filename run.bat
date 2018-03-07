@echo off
explorer "http://localhost:6789/game-engine.php"
.\php\php -c .\php\ -S localhost:6789 -t .\www\public_html