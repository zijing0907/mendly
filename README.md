# WAD_Asgmt

The repository is for collaboration in Web Application Design assignment.

## Database configuration

Database credentials are provided through environment variables rather than source code:

```text
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=your-local-password
DB_NAME=mendly
```

`DB_PASSWORD` is required, including when the intended local password is empty. Do not commit credentials to this repository.

The seed accounts in `database/database.sql` use bcrypt password hashes. The shared development password is `ChangeMe!2026`; change it before deploying or sharing the application.
