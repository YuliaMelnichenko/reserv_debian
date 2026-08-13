-- Safe one-time cleanup: removes only already expired remembered-login tokens.
DELETE FROM auth_remember_tokens
WHERE EXPIRES_AT < NOW();
