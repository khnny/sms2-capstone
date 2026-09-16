#!/usr/bin/env python3
import os
import sys
from ftplib import FTP_TLS, error_perm

# Read credentials from environment — never hardcode secrets in the repo.
HOSTS = [h.strip() for h in os.environ.get('SMS2_FTP_HOSTS', 'ftpupload.net,ftp.epizy.com').split(',') if h.strip()]
USER = os.environ.get('SMS2_FTP_USER', '')
PASS = os.environ.get('SMS2_FTP_PASS', '')
LOCAL = os.environ.get('SMS2_FTP_LOCAL', r'C:\xampp\htdocs\sms2_deploy_staging')
REMOTE_ROOT = os.environ.get('SMS2_FTP_REMOTE', '/htdocs')

if not USER or not PASS:
    print('Set SMS2_FTP_USER and SMS2_FTP_PASS environment variables before running.', file=sys.stderr)
    sys.exit(1)

def upload_tree(ftp, local_dir, remote_dir):
    try:
        ftp.mkd(remote_dir)
    except error_perm:
        pass
    ftp.cwd(remote_dir)
    for name in sorted(os.listdir(local_dir)):
        local_path = os.path.join(local_dir, name)
        if os.path.isdir(local_path):
            upload_tree(ftp, local_path, name)
            ftp.cwd('..')
        else:
            print(f'Uploading {remote_dir}/{name}')
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {name}', f)

for host in HOSTS:
    print(f'Trying {host}...')
    try:
        ftp = FTP_TLS(host)
        ftp.login(USER, PASS)
        ftp.prot_p()
        ftp.cwd(REMOTE_ROOT)
        upload_tree(ftp, LOCAL, '.')
        ftp.quit()
        print(f'Upload finished via {host}')
        sys.exit(0)
    except Exception as e:
        print(f'{host} failed: {e}')

print('All FTP hosts failed.', file=sys.stderr)
sys.exit(1)
