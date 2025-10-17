#!/usr/bin/env python3
"""
Laravel Application Security Scanner
WARNING: Use only on applications you own or have explicit permission to test.
Unauthorized security testing may be illegal.
"""

import requests
import re
import sys
import argparse
from urllib.parse import urljoin, urlparse
from typing import List, Dict
import json
from datetime import datetime

class LaravelSecurityScanner:
    def __init__(self, base_url: str, timeout: int = 10, verify_ssl: bool = True):
        self.base_url = base_url.rstrip('/')
        self.timeout = timeout
        self.verify_ssl = verify_ssl
        self.session = requests.Session()
        self.session.verify = verify_ssl
        self.vulnerabilities = []

        # Disable SSL warnings if verification is disabled
        if not verify_ssl:
            import urllib3
            urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    def log_vulnerability(self, severity: str, vuln_type: str, description: str, details: str = ""):
        """Log discovered vulnerability"""
        self.vulnerabilities.append({
            'severity': severity,
            'type': vuln_type,
            'description': description,
            'details': details,
            'timestamp': datetime.now().isoformat()
        })

    def check_debug_mode(self) -> bool:
        """Check if Laravel debug mode is enabled"""
        print("[*] Checking for debug mode exposure...")
        try:
            # Trigger an error by accessing invalid route
            response = self.session.get(
                f"{self.base_url}/this-route-should-not-exist-12345",
                timeout=self.timeout,
                allow_redirects=False
            )

            # Look for Laravel debug page indicators
            debug_indicators = [
                'Whoops\\Exception\\ErrorException',
                'Illuminate\\',
                'laravel/framework',
                'Stack trace:',
                'symfony/debug'
            ]

            if any(indicator in response.text for indicator in debug_indicators):
                self.log_vulnerability(
                    'HIGH',
                    'Debug Mode Enabled',
                    'Laravel application is running with debug mode enabled',
                    'Debug mode exposes sensitive information including file paths, database queries, and environment variables'
                )
                return True
        except Exception as e:
            print(f"[-] Error checking debug mode: {e}")
        return False

    def check_env_exposure(self) -> bool:
        """Check for exposed .env file"""
        print("[*] Checking for exposed .env file...")
        env_paths = ['/.env', '/.env.backup', '/.env.old', '/.env.save', '/.env.prod']

        for path in env_paths:
            try:
                response = self.session.get(
                    f"{self.base_url}{path}",
                    timeout=self.timeout
                )

                if response.status_code == 200 and ('APP_KEY' in response.text or 'DB_PASSWORD' in response.text):
                    self.log_vulnerability(
                        'CRITICAL',
                        'Environment File Exposed',
                        f'.env file is publicly accessible at {path}',
                        'The .env file contains sensitive configuration including database credentials and API keys'
                    )
                    return True
            except Exception as e:
                continue
        return False

    def check_csrf_protection(self) -> bool:
        """Check CSRF protection on forms"""
        print("[*] Checking CSRF protection...")
        try:
            response = self.session.get(f"{self.base_url}", timeout=self.timeout)

            # Find forms in the page
            forms = re.findall(r'<form[^>]*>(.*?)</form>', response.text, re.DOTALL | re.IGNORECASE)

            vulnerable_forms = 0
            for form in forms:
                # Check if form has POST method
                if re.search(r'method\s*=\s*["\']post["\']', form, re.IGNORECASE):
                    # Check for CSRF token
                    if '_token' not in form and 'csrf' not in form.lower():
                        vulnerable_forms += 1

            if vulnerable_forms > 0:
                self.log_vulnerability(
                    'MEDIUM',
                    'Missing CSRF Protection',
                    f'Found {vulnerable_forms} POST form(s) without CSRF tokens',
                    'Forms without CSRF protection are vulnerable to Cross-Site Request Forgery attacks'
                )
                return True
        except Exception as e:
            print(f"[-] Error checking CSRF: {e}")
        return False

    def check_sql_injection(self) -> bool:
        """Basic SQL injection detection"""
        print("[*] Checking for SQL injection vulnerabilities...")

        # Common SQL injection payloads
        payloads = ["'", "1' OR '1'='1", "1' AND '1'='2", "'; DROP TABLE users--"]

        try:
            # Try to find URL parameters
            response = self.session.get(f"{self.base_url}", timeout=self.timeout)

            # Look for links with parameters
            links = re.findall(r'href=["\']([^"\']*\?[^"\']*)["\']', response.text)

            for link in links[:5]:  # Test first 5 parametrized links
                full_url = urljoin(self.base_url, link)
                parsed = urlparse(full_url)

                if parsed.query:
                    for payload in payloads:
                        test_url = full_url.replace(parsed.query.split('=')[1], payload)
                        try:
                            test_response = self.session.get(test_url, timeout=self.timeout)

                            # Check for SQL error messages
                            sql_errors = [
                                'SQL syntax',
                                'mysql_fetch',
                                'SQLSTATE',
                                'SQLException',
                                'Illuminate\\Database\\QueryException'
                            ]

                            if any(error in test_response.text for error in sql_errors):
                                self.log_vulnerability(
                                    'CRITICAL',
                                    'SQL Injection Vulnerability',
                                    f'Possible SQL injection at {full_url}',
                                    f'Payload "{payload}" triggered a database error'
                                )
                                return True
                        except:
                            continue
        except Exception as e:
            print(f"[-] Error checking SQL injection: {e}")
        return False

    def check_xss(self) -> bool:
        """Basic XSS vulnerability detection"""
        print("[*] Checking for XSS vulnerabilities...")

        xss_payload = '<script>alert("XSS")</script>'

        try:
            response = self.session.get(f"{self.base_url}", timeout=self.timeout)
            links = re.findall(r'href=["\']([^"\']*\?[^"\']*)["\']', response.text)

            for link in links[:5]:
                full_url = urljoin(self.base_url, link)
                parsed = urlparse(full_url)

                if parsed.query:
                    param_name = parsed.query.split('=')[0]
                    test_url = f"{full_url.split('?')[0]}?{param_name}={xss_payload}"

                    try:
                        test_response = self.session.get(test_url, timeout=self.timeout)

                        if xss_payload in test_response.text:
                            self.log_vulnerability(
                                'HIGH',
                                'Cross-Site Scripting (XSS)',
                                f'Reflected XSS vulnerability found at {full_url}',
                                'User input is reflected in the page without proper sanitization'
                            )
                            return True
                    except:
                        continue
        except Exception as e:
            print(f"[-] Error checking XSS: {e}")
        return False

    def check_sensitive_files(self) -> bool:
        """Check for exposed sensitive files"""
        print("[*] Checking for exposed sensitive files...")

        sensitive_paths = [
            '/storage/logs/laravel.log',
            '/composer.json',
            '/composer.lock',
            '/phpunit.xml',
            '/webpack.mix.js',
            '/.git/config',
            '/.git/HEAD',
            '/database/database.sqlite',
            '/storage/app/.gitignore'
        ]

        found = False
        for path in sensitive_paths:
            try:
                response = self.session.get(
                    f"{self.base_url}{path}",
                    timeout=self.timeout
                )

                if response.status_code == 200 and len(response.content) > 0:
                    self.log_vulnerability(
                        'MEDIUM',
                        'Sensitive File Exposure',
                        f'Sensitive file accessible at {path}',
                        f'File returned {len(response.content)} bytes'
                    )
                    found = True
            except:
                continue
        return found

    def check_security_headers(self) -> bool:
        """Check for missing security headers"""
        print("[*] Checking security headers...")

        try:
            response = self.session.get(f"{self.base_url}", timeout=self.timeout)
            headers = response.headers

            missing_headers = []

            if 'X-Frame-Options' not in headers:
                missing_headers.append('X-Frame-Options (Clickjacking protection)')

            if 'X-Content-Type-Options' not in headers:
                missing_headers.append('X-Content-Type-Options (MIME sniffing protection)')

            if 'X-XSS-Protection' not in headers:
                missing_headers.append('X-XSS-Protection')

            if 'Strict-Transport-Security' not in headers and self.base_url.startswith('https'):
                missing_headers.append('Strict-Transport-Security (HSTS)')

            if 'Content-Security-Policy' not in headers:
                missing_headers.append('Content-Security-Policy')

            if missing_headers:
                self.log_vulnerability(
                    'LOW',
                    'Missing Security Headers',
                    'Application is missing important security headers',
                    'Missing headers: ' + ', '.join(missing_headers)
                )
                return True
        except Exception as e:
            print(f"[-] Error checking headers: {e}")
        return False

    def check_directory_listing(self) -> bool:
        """Check for directory listing vulnerability"""
        print("[*] Checking for directory listing...")

        directories = ['/storage', '/public', '/uploads', '/files']

        for directory in directories:
            try:
                response = self.session.get(
                    f"{self.base_url}{directory}",
                    timeout=self.timeout
                )

                if 'Index of' in response.text or '<title>Directory listing for' in response.text:
                    self.log_vulnerability(
                        'MEDIUM',
                        'Directory Listing Enabled',
                        f'Directory listing is enabled at {directory}',
                        'Attackers can browse and download files from this directory'
                    )
                    return True
            except:
                continue
        return False

    def run_scan(self) -> Dict:
        """Run all security checks"""
        print(f"\n{'='*60}")
        print(f"Laravel Security Scanner")
        print(f"Target: {self.base_url}")
        print(f"Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"{'='*60}\n")

        # Run all checks
        self.check_debug_mode()
        self.check_env_exposure()
        self.check_csrf_protection()
        self.check_sql_injection()
        self.check_xss()
        self.check_sensitive_files()
        self.check_security_headers()
        self.check_directory_listing()

        # Generate report
        return self.generate_report()

    def generate_report(self) -> Dict:
        """Generate vulnerability report"""
        print(f"\n{'='*60}")
        print(f"SCAN RESULTS")
        print(f"{'='*60}\n")

        if not self.vulnerabilities:
            print("[+] No vulnerabilities detected!")
            return {'status': 'clean', 'vulnerabilities': []}

        # Count by severity
        severity_count = {'CRITICAL': 0, 'HIGH': 0, 'MEDIUM': 0, 'LOW': 0}

        for vuln in self.vulnerabilities:
            severity_count[vuln['severity']] += 1

            print(f"[!] {vuln['severity']} - {vuln['type']}")
            print(f"    Description: {vuln['description']}")
            if vuln['details']:
                print(f"    Details: {vuln['details']}")
            print()

        print(f"{'='*60}")
        print(f"SUMMARY")
        print(f"{'='*60}")
        print(f"Critical: {severity_count['CRITICAL']}")
        print(f"High:     {severity_count['HIGH']}")
        print(f"Medium:   {severity_count['MEDIUM']}")
        print(f"Low:      {severity_count['LOW']}")
        print(f"Total:    {len(self.vulnerabilities)}")
        print(f"{'='*60}\n")

        return {
            'status': 'vulnerabilities_found',
            'summary': severity_count,
            'vulnerabilities': self.vulnerabilities
        }

def main():
    parser = argparse.ArgumentParser(
        description='Laravel Application Security Scanner',
        epilog='WARNING: Only use on applications you own or have permission to test!'
    )
    parser.add_argument('url', help='Target Laravel application URL (e.g., http://myapp.local, http://localhost:8000)')
    parser.add_argument('-t', '--timeout', type=int, default=10, help='Request timeout in seconds (default: 10)')
    parser.add_argument('-o', '--output', help='Output file for JSON report')
    parser.add_argument('-k', '--insecure', action='store_true',
                       help='Disable SSL certificate verification (for self-signed certs)')
    parser.add_argument('--host-header', help='Custom Host header for virtual host testing')

    args = parser.parse_args()

    # Validate URL
    if not args.url.startswith(('http://', 'https://')):
        print("[-] Error: URL must start with http:// or https://")
        sys.exit(1)

    # Check for local/virtual host indicators
    parsed_url = urlparse(args.url)
    is_local = any(domain in parsed_url.netloc.lower() for domain in
                   ['localhost', '127.0.0.1', '::1', '.local', '.test', '.dev'])

    if is_local:
        print(f"[*] Detected local/virtual host environment: {parsed_url.netloc}")
        if args.url.startswith('https://') and not args.insecure:
            print("[!] TIP: Use --insecure flag if you're using self-signed SSL certificates")

    # Run scanner
    scanner = LaravelSecurityScanner(args.url, timeout=args.timeout, verify_ssl=not args.insecure)

    # Add custom host header if specified
    if args.host_header:
        scanner.session.headers['Host'] = args.host_header
        print(f"[*] Using custom Host header: {args.host_header}")

    try:
        results = scanner.run_scan()

        # Save to file if requested
        if args.output:
            with open(args.output, 'w') as f:
                json.dump(results, f, indent=2)
            print(f"[+] Report saved to {args.output}")

    except KeyboardInterrupt:
        print("\n[-] Scan interrupted by user")
        sys.exit(1)
    except requests.exceptions.ConnectionError:
        print(f"[-] Error: Unable to connect to {args.url}")
        print("[*] Make sure the server is running and accessible")
        if is_local:
            print("[*] For virtual hosts, verify your hosts file configuration")
        sys.exit(1)
    except Exception as e:
        print(f"[-] Error during scan: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()