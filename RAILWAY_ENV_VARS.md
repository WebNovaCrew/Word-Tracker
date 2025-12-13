# Railway Environment Variables for PHP Backend

Set these in your Railway PHP backend service:

```
MYSQLHOST=shuttle.proxy.rlwy.net
MYSQLPORT=36666
MYSQLUSER=root
MYSQLPASSWORD=WiGhctjnxmSBDWukfTiCLzvLGrXRmQdt
MYSQLDATABASE=railway
```

## How to Set in Railway:

1. Go to your Railway project
2. Select your PHP backend service
3. Go to "Variables" tab
4. Add each variable above
5. Redeploy the service

## Verification:

After setting variables, your PHP backend will connect to:
- Host: shuttle.proxy.rlwy.net
- Port: 36666
- Database: railway
- User: root
