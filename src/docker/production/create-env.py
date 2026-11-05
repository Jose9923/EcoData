#!/usr/bin/env python3
"""Create private deployment settings without printing or overwriting secrets."""
import argparse
import base64
import os
from pathlib import Path
import secrets
import stat
from urllib.parse import urlsplit


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', required=True, type=Path, help='Absolute private destination outside the repository')
    parser.add_argument('--url', required=True, help='HTTPS origin')
    parser.add_argument('--app-key-file', type=Path, help='Private file containing the existing APP_KEY when restoring encrypted data')
    args = parser.parse_args()
    repo = Path(__file__).resolve().parents[3]
    url = urlsplit(args.url)
    if url.scheme != 'https' or not url.hostname or url.username or url.password or url.query or url.fragment or url.path not in ('', '/') or any(c in args.url for c in '\r\n\'"$#'):
        parser.error('--url must be an HTTPS origin without credentials, path, query or fragment')
    output = args.output
    if not output.is_absolute():
        parser.error('--output must be absolute')
    output = output.resolve()
    if output.is_relative_to(repo):
        parser.error('Secrets must be stored outside the repository')
    if output.exists():
        parser.error('Destination exists; it will not be overwritten')
    if args.app_key_file:
        source = args.app_key_file.resolve(strict=True)
        if not source.is_file() or stat.S_IMODE(source.stat().st_mode) & 0o077:
            parser.error('The existing key file must be private (0600 or stricter)')
        key = source.read_text(encoding='utf-8').strip()
        try:
            valid = key.startswith('base64:') and len(base64.b64decode(key[7:], validate=True)) == 32
        except ValueError:
            valid = False
        if not valid:
            parser.error('The existing key must encode exactly 32 bytes')
    else:
        key = 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode('ascii')
    output.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    if stat.S_IMODE(output.parent.stat().st_mode) & 0o077:
        parser.error('Destination parent must be private (0700 or stricter)')
    secrets_dir = output.parent / 'secrets'
    if secrets_dir.exists():
        parser.error('The secrets directory exists; existing credentials will not be overwritten')
    if any(ord(c) in (10, 13, 34, 39, 35, 36, 92) for c in str(secrets_dir)):
        parser.error('The private path contains unsupported env-file characters')
    values = {
        'APP_ENV': 'production', 'APP_DEBUG': 'false', 'APP_URL': args.url.rstrip('/'),
        'APP_KEY': key, 'APP_TIMEZONE': 'America/Bogota', 'ECODATA_IMAGE_TAG': 'production',
        'ECODATA_SECRETS_DIR': str(secrets_dir),
        'DB_DATABASE': 'ecodata_production', 'DB_USERNAME': 'ecodata_app',
        'DB_PASSWORD': secrets.token_hex(32), 'DB_MIGRATOR_USERNAME': 'ecodata_migrator',
        'DB_MIGRATOR_PASSWORD': secrets.token_hex(32), 'MYSQL_ROOT_PASSWORD': secrets.token_hex(32),
        'MAIL_MAILER': 'array', 'MAIL_SCHEME': 'smtp', 'MAIL_HOST': '', 'MAIL_PORT': '587',
        'MAIL_USERNAME': '', 'MAIL_PASSWORD': '', 'MAIL_FROM_ADDRESS': 'notificaciones@example.invalid',
        'SESSION_SECURE_COOKIE': 'true', 'AUTH_REGISTRATION_ENABLED': 'false',
        'ECODATA_ALLOW_DEMO_SEED': 'false',
    }
    secrets_dir.mkdir(mode=0o700)
    for variable, filename in (('MYSQL_ROOT_PASSWORD', 'mysql_root_password'), ('DB_PASSWORD', 'db_password'), ('DB_MIGRATOR_PASSWORD', 'db_migrator_password')):
        secret_path = secrets_dir / filename
        secret_fd = os.open(secret_path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o444)
        with os.fdopen(secret_fd, 'w', encoding='utf-8') as stream:
            stream.write(values[variable] + '\n')
        secret_path.chmod(0o444)
    fd = os.open(output, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(fd, 'w', encoding='utf-8') as stream:
        stream.write('# EcoData private production configuration. Never commit this file.\n')
        stream.writelines(f'{key}={value}\n' for key, value in values.items())
    print('Private production configuration created; secret values were not printed.')


if __name__ == '__main__':
    main()
