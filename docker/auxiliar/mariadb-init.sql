-- Dar al usuario auxiliar permisos para crear bases de datos de tenants
GRANT ALL PRIVILEGES ON *.* TO 'auxiliar'@'%';
FLUSH PRIVILEGES;
