#!/usr/bin/env python3
# proxy_relay.py - Run this on your VPS
# python3 proxy_relay.py

import socket
import threading
import select
import sys
import os
import json
import hashlib
import time

# ========== CONFIG ==========
LISTEN_HOST = "0.0.0.0"
LISTEN_PORT = 1080  # SOCKS5 port
AUTH_USERNAME = "proxyuser"
AUTH_PASSWORD = "proxypass123"
# ============================

def socks5_handshake(client):
    """Handle SOCKS5 handshake"""
    # Read auth methods
    data = client.recv(2)
    if not data or data[0] != 0x05:
        return False
    nmethods = data[1]
    methods = client.recv(nmethods)
    
    # We support username/password auth (0x02) and no auth (0x00)
    if 0x02 in methods:
        client.send(bytes([0x05, 0x02]))  # Request username/password auth
        # Read auth
        auth = client.recv(2)
        if not auth or auth[0] != 0x01:
            return False
        ulen = auth[1]
        username = client.recv(ulen).decode()
        plen_data = client.recv(1)
        if not plen_data:
            return False
        plen = plen_data[0]
        password = client.recv(plen).decode()
        
        if username == AUTH_USERNAME and password == AUTH_PASSWORD:
            client.send(bytes([0x01, 0x00]))  # Auth success
        else:
            client.send(bytes([0x01, 0x01]))  # Auth fail
            return False
    elif 0x00 in methods:
        client.send(bytes([0x05, 0x00]))  # No auth
    else:
        client.send(bytes([0x05, 0xFF]))
        return False
    
    return True

def socks5_connect(client):
    """Handle SOCKS5 connect request"""
    data = client.recv(4)
    if not data or data[0] != 0x05:
        return None, None
    
    cmd = data[1]
    if cmd != 0x01:  # Only support CONNECT
        client.send(bytes([0x05, 0x07, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]))
        return None, None
    
    atype = data[3]
    
    if atype == 0x01:  # IPv4
        host = socket.inet_ntoa(client.recv(4))
    elif atype == 0x03:  # Domain
        dlen = client.recv(1)[0]
        host = client.recv(dlen).decode()
    elif atype == 0x04:  # IPv6
        host = socket.inet_ntop(socket.AF_INET6, client.recv(16))
    else:
        return None, None
    
    port = int.from_bytes(client.recv(2), 'big')
    
    # Accept the connection
    client.send(bytes([0x05, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]))
    
    return host, port

def forward(src, dst):
    """Bidirectional forward data"""
    try:
        while True:
            r, _, _ = select.select([src, dst], [], [], 60)
            if not r:
                break
            for sock in r:
                data = sock.recv(65536)
                if not data:
                    return
                if sock is src:
                    dst.send(data)
                else:
                    src.send(data)
    except:
        pass

def handle_client(client, addr):
    print(f"[+] Connection from {addr[0]}:{addr[1]}")
    try:
        if not socks5_handshake(client):
            client.close()
            return
        
        host, port = socks5_connect(client)
        if not host:
            client.close()
            return
        
        print(f"[*] Connecting to {host}:{port}...")
        
        remote = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        remote.settimeout(10)
        remote.connect((host, port))
        print(f"[+] Connected to {host}:{port}")
        
        forward(client, remote)
    except Exception as e:
        print(f"[-] Error: {e}")
    finally:
        client.close()
        if 'remote' in locals():
            remote.close()
    print(f"[-] {addr[0]}:{addr[1]} disconnected")

def main():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    
    try:
        server.bind((LISTEN_HOST, LISTEN_PORT))
        server.listen(200)
        print(f"[*] SOCKS5 Proxy listening on {LISTEN_HOST}:{LISTEN_PORT}")
        print(f"[*] Auth: {AUTH_USERNAME}:{AUTH_PASSWORD}")
        
        while True:
            client, addr = server.accept()
            threading.Thread(target=handle_client, args=(client, addr), daemon=True).start()
    except KeyboardInterrupt:
        print("\n[*] Shutting down...")
    finally:
        server.close()

if __name__ == "__main__":
    main()
