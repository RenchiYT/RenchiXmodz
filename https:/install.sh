#!/bin/bash
# install.sh - Run this on your VPS as root
# curl -sL https://yourdomain.com/install.sh | bash

echo "================================="
echo "  Installing Proxy Relay Server  "
echo "================================="

# Update system
apt update && apt upgrade -y

# Install Python3 if not present
apt install python3 python3-pip screen -y

# Create directory
mkdir -p /opt/proxyrelay
cd /opt/proxyrelay

# Download the proxy script (or paste it manually)
cat > /opt/proxyrelay/proxy_relay.py << 'PYEOF'
# Paste the proxy_relay.py content here
# Or upload it via SCP
PYEOF

# Create systemd service
cat > /etc/systemd/system/proxyrelay.service << 'EOF'
[Unit]
Description=Proxy Relay Service
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/proxyrelay
ExecStart=/usr/bin/python3 /opt/proxyrelay/proxy_relay.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Enable and start
systemctl daemon-reload
systemctl enable proxyrelay
systemctl start proxyrelay

echo "[+] Proxy Relay installed and running on port 1080"
echo "[+] Status: systemctl status proxyrelay"
