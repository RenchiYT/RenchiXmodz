<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Free Fire Proxy Server</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .header {
            text-align: center; padding: 40px 0;
        }
        .header h1 { font-size: 2.5em; background: linear-gradient(45deg, #f093fb, #f5576c);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header p { color: #aaa; margin-top: 10px; font-size: 1.1em; }
        
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-discord {
            background: #5865F2; color: white;
        }
        .btn-discord:hover { background: #4752C4; }
        .btn-primary {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-danger {
            background: #e74c3c; color: white;
        }
        .btn-danger:hover { background: #c0392b; }
        
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 1em;
            margin: 8px 0;
        }
        input:focus { outline: none; border-color: #f5576c; }
        
        label { display: block; margin-top: 12px; color: #ccc; font-size: 0.9em; }
        
        .server-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .server-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .server-card .flag { font-size: 2em; margin-bottom: 8px; }
