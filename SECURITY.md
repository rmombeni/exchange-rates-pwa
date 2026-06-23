# 🔒 Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 3.0.x   | ✅ |
| 2.0.x   | ✅ |
| < 2.0   | ❌ |

## Reporting a Vulnerability

If you discover a security vulnerability:

1. **Do NOT open a public issue**
2. Email us at: `security@mozili.ir`
3. Wait for a response (within 72 hours)

### What to include:
- Detailed description
- Steps to reproduce
- Affected version
- Suggested fix (if any)

## Security Measures

- Password hashing with bcrypt
- Session management
- XSS protection with htmlspecialchars
- CSRF protection
- SQL injection prevention (using JSON instead of SQL)

---

**Thanks for helping keep this project secure!** 🔒
