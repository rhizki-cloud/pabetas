UPDATE users SET password = '$2y$12$V7tqCGTl163n44bX5S7dWeYIDoabnwbNgEYqVg5yEsK7KKo6dh34.', role='guru', status=1 WHERE username='guru';
UPDATE users SET password = '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', role='murid', status=1 WHERE username='siswa';

INSERT INTO users(name, username, password, role, status, avatar_key)
SELECT 'Guru PABETAS', 'guru', '$2y$12$V7tqCGTl163n44bX5S7dWeYIDoabnwbNgEYqVg5yEsK7KKo6dh34.', 'guru', 1, 'book'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='guru');

INSERT INTO users(name, username, password, role, status, avatar_key)
SELECT 'Siswa Demo', 'siswa', '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', 'murid', 1, 'rocket'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='siswa');
