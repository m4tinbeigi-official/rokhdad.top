# 🔐 Security Hardening Checklist

## Pre-Deployment

- [ ] Change all default passwords
  - [ ] Database root password
  - [ ] Database user password
  - [ ] Redis password
  - [ ] Admin user password

- [ ] Validate environment variables
  - [ ] `APP_KEY` is set and strong
  - [ ] All payment merchant IDs are production
  - [ ] SMS API keys are production
  - [ ] Email credentials are production
  - [ ] No debug tokens in env

- [ ] Update dependencies
  - [ ] `composer update` (Laravel)
  - [ ] `npm update` (Frontend)
  - [ ] `pip install --upgrade` (Workers)

## Server Hardening

- [ ] SSH Configuration
  - [ ] Disable root login
  - [ ] Disable password auth (use keys only)
  - [ ] Change default SSH port
  - [ ] Whitelist IPs if possible

- [ ] Firewall Rules
  - [ ] Allow only 80, 443, 22 (SSH)
  - [ ] Block all other ports
  - [ ] Rate limit SSH attempts
  - [ ] Rate limit API endpoints

- [ ] File Permissions
  - [ ] No 777 permissions on storage
  - [ ] Storage files: 755 (dirs), 644 (files)
  - [ ] No `.env` in web root
  - [ ] No `.git` exposed

## Database Security

- [ ] MariaDB
  - [ ] Non-root user only (rockchat)
  - [ ] Limited privileges (no DROP/ALTER)
  - [ ] Remote connections disabled
  - [ ] Backups encrypted
  - [ ] Binlog enabled for recovery

- [ ] MongoDB
  - [ ] Authentication enabled
  - [ ] Replication keyfile secured
  - [ ] Network restricted to localhost
  - [ ] No anonymous access

## Application Security

- [ ] Authentication
  - [ ] Password minimum 12 characters
  - [ ] BCRYPT_ROUNDS=12
  - [ ] Rate limit login: 5 attempts/hour
  - [ ] 2FA for admin users
  - [ ] Session timeout: 24 hours

- [ ] API Security
  - [ ] CORS whitelist: only rokhdad.top
  - [ ] HTTPS enforced
  - [ ] HSTS header: max-age=31536000
  - [ ] CSP headers configured
  - [ ] X-Frame-Options: DENY
  - [ ] X-Content-Type-Options: nosniff

- [ ] Input Validation
  - [ ] All user input validated server-side
  - [ ] SQL injection prevention (prepared statements)
  - [ ] XSS protection (sanitize output)
  - [ ] CSRF tokens on state-changing requests
  - [ ] File upload restrictions (whitelist types)

- [ ] Payment Security
  - [ ] No PAN storage (use tokenization)
  - [ ] Payment webhooks signed
  - [ ] Callback URL whitelisted
  - [ ] Timeout for payment verification
  - [ ] Log all payment events

- [ ] Data Protection
  - [ ] Sensitive fields encrypted at rest
  - [ ] Database user passwords hashed
  - [ ] API keys rotated quarterly
  - [ ] PII logged minimally
  - [ ] GDPR compliance: data retention policies

## Monitoring & Logging

- [ ] Logging
  - [ ] All authentication attempts logged
  - [ ] All payment events logged
  - [ ] All admin actions logged
  - [ ] Log retention: 90 days minimum
  - [ ] Logs not accessible from web

- [ ] Monitoring
  - [ ] Uptime monitoring enabled
  - [ ] Error rate alerts configured
  - [ ] Payment failure alerts
  - [ ] Backup completion verification
  - [ ] Disk space alerts (80% threshold)

- [ ] Threat Detection
  - [ ] WAF rules enabled
  - [ ] DDoS protection enabled
  - [ ] Intrusion detection enabled
  - [ ] Rate limiting on all endpoints
  - [ ] Anomaly detection alerts

## SSL/TLS

- [ ] Certificate
  - [ ] Valid certificate for rokhdad.top
  - [ ] Certificate auto-renewal enabled
  - [ ] Key size: 2048 bits minimum
  - [ ] Cipher suite: modern (no RC4, DES)

- [ ] Configuration
  - [ ] TLS 1.2+ only
  - [ ] HTTP → HTTPS redirect enforced
  - [ ] HSTS preload enabled
  - [ ] Certificate pinning (optional)

## Compliance

- [ ] GDPR
  - [ ] Privacy policy published
  - [ ] Data export functionality
  - [ ] Right to be forgotten implemented
  - [ ] Data processing agreement with providers

- [ ] PCI-DSS (if handling cards)
  - [ ] No cardholder data stored
  - [ ] Use tokenization gateway
  - [ ] Quarterly security scans
  - [ ] Annual penetration testing

- [ ] Iran-Specific
  - [ ] Comply with local payment regulations
  - [ ] Currency: IRR validation
  - [ ] Persian text validation
  - [ ] No prohibited payment methods

## Backup & Recovery

- [ ] Backups
  - [ ] Daily automated backups
  - [ ] Backups encrypted
  - [ ] Off-site backup copies
  - [ ] Backup integrity tested monthly

- [ ] Recovery
  - [ ] RTO: 1 hour
  - [ ] RPO: 1 day
  - [ ] Recovery plan documented
  - [ ] Recovery drill: quarterly

## Access Control

- [ ] Admin Panel
  - [ ] Two-factor authentication required
  - [ ] IP whitelist enabled
  - [ ] Activity logging enabled
  - [ ] Session timeout: 4 hours

- [ ] Server Access
  - [ ] SSH keys only (no passwords)
  - [ ] Limited admin users
  - [ ] Sudo logging enabled
  - [ ] Access reviewed quarterly

- [ ] Secrets Management
  - [ ] .env not in git
  - [ ] Secrets in .gitignore
  - [ ] Environment variables per deployment
  - [ ] No secrets in logs

## Testing

- [ ] Security Testing
  - [ ] OWASP Top 10 checklist
  - [ ] SQL injection test
  - [ ] XSS test
  - [ ] CSRF test
  - [ ] Authentication bypass test

- [ ] Penetration Testing
  - [ ] Annual third-party pen test
  - [ ] Vulnerability scanner results
  - [ ] Critical issues remediated
  - [ ] Medium issues addressed

## Documentation

- [ ] Security Policy
  - [ ] Incident response plan
  - [ ] Data breach notification procedure
  - [ ] Vulnerability disclosure policy
  - [ ] Security contacts documented

- [ ] Runbooks
  - [ ] Emergency access procedure
  - [ ] Incident response workflow
  - [ ] Data breach response
  - [ ] Backup restore procedure

## Sign-Off

- [ ] Security lead review: ________________ Date: _______
- [ ] Project owner approval: ________________ Date: _______
- [ ] Deployment authorized: ________________ Date: _______

---

**Notes:** Complete all items before production deployment.
All "X" marks must be converted to checks [✓].
