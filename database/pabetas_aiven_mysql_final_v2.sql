SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS academic_assessment_answers;
DROP TABLE IF EXISTS academic_assessments;
DROP TABLE IF EXISTS assessment_template_questions;
DROP TABLE IF EXISTS assessment_templates;
DROP TABLE IF EXISTS learning_flow_steps;
DROP TABLE IF EXISTS material_drag_games;
DROP TABLE IF EXISTS learning_reflections;
DROP TABLE IF EXISTS reflection_prompts;
DROP TABLE IF EXISTS student_learning_progress;
DROP TABLE IF EXISTS remedial_assignments;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS live_game_powerup_uses;
DROP TABLE IF EXISTS live_game_answers;
DROP TABLE IF EXISTS live_game_players;
DROP TABLE IF EXISTS live_games;
DROP TABLE IF EXISTS responses;
DROP TABLE IF EXISTS game_sessions;
DROP TABLE IF EXISTS team_members;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS learning_materials;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('guru','murid') NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  avatar_key VARCHAR(40) NOT NULL DEFAULT 'rocket',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nis VARCHAR(40) NULL,
  class_name VARCHAR(60) DEFAULT 'III A',
  gender VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE learning_materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  content LONGTEXT NOT NULL,
  step_order INT NOT NULL DEFAULT 1,
  media_type ENUM('text','image','video') DEFAULT 'text',
  media_url TEXT NULL,
  drag_json TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_material_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mode ENUM('individu','tim') NOT NULL DEFAULT 'individu',
  type ENUM('multiple','short','essay') NOT NULL DEFAULT 'multiple',
  group_label VARCHAR(80) NOT NULL DEFAULT 'Umum',
  difficulty ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'mudah',
  prompt TEXT NOT NULL,
  options_json TEXT NULL,
  answer_key VARCHAR(255) NOT NULL,
  score INT NOT NULL DEFAULT 10,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_question_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(12) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  class_name VARCHAR(60) DEFAULT 'III A',
  mode ENUM('tim') DEFAULT 'tim',
  status ENUM('open','running','closed') DEFAULT 'open',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  start_at DATETIME NULL,
  finish_at DATETIME NULL,
  CONSTRAINT fk_room_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_team_room FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE team_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  student_id INT NOT NULL,
  role_name VARCHAR(80) NULL,
  CONSTRAINT fk_member_team FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_member_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE live_games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(12) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  mode ENUM('individu','tim') NOT NULL DEFAULT 'individu',
  status ENUM('waiting','running','finished') NOT NULL DEFAULT 'waiting',
  phase ENUM('waiting','question','ranking','final') NOT NULL DEFAULT 'waiting',
  current_index INT NOT NULL DEFAULT 0,
  question_ids_json LONGTEXT NOT NULL,
  question_seconds INT NOT NULL DEFAULT 20,
  ranking_seconds INT NOT NULL DEFAULT 5,
  created_by INT NULL,
  room_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  phase_started_at DATETIME NULL,
  share_result TINYINT(1) NOT NULL DEFAULT 1,
  control_mode ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  show_correct_answer TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_live_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_live_room FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE live_game_players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  live_game_id INT NOT NULL,
  user_id INT NOT NULL,
  team_id INT NULL,
  display_name VARCHAR(160) NOT NULL,
  avatar_key VARCHAR(40) NOT NULL DEFAULT 'rocket',
  score INT NOT NULL DEFAULT 0,
  total_correct INT NOT NULL DEFAULT 0,
  total_wrong INT NOT NULL DEFAULT 0,
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_seen DATETIME NULL,
  UNIQUE KEY uq_live_player(live_game_id, user_id),
  CONSTRAINT fk_live_player_game FOREIGN KEY(live_game_id) REFERENCES live_games(id) ON DELETE CASCADE,
  CONSTRAINT fk_live_player_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_live_player_team FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE live_game_powerup_uses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  live_game_id INT NOT NULL,
  player_id INT NOT NULL,
  user_id INT NOT NULL,
  question_index INT NOT NULL,
  powerup_type ENUM('ladder_help','fifty','bonus_time') NOT NULL,
  data_json TEXT NULL,
  used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_powerup_once(live_game_id, player_id, powerup_type),
  CONSTRAINT fk_powerup_game FOREIGN KEY(live_game_id) REFERENCES live_games(id) ON DELETE CASCADE,
  CONSTRAINT fk_powerup_player FOREIGN KEY(player_id) REFERENCES live_game_players(id) ON DELETE CASCADE,
  CONSTRAINT fk_powerup_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE live_game_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  live_game_id INT NOT NULL,
  player_id INT NOT NULL,
  user_id INT NOT NULL,
  question_id INT NOT NULL,
  question_index INT NOT NULL,
  answer TEXT NULL,
  is_correct TINYINT(1) NULL,
  score INT NOT NULL DEFAULT 0,
  response_time_ms INT NOT NULL DEFAULT 0,
  answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_live_answer(live_game_id, player_id, question_index),
  CONSTRAINT fk_live_answer_game FOREIGN KEY(live_game_id) REFERENCES live_games(id) ON DELETE CASCADE,
  CONSTRAINT fk_live_answer_player FOREIGN KEY(player_id) REFERENCES live_game_players(id) ON DELETE CASCADE,
  CONSTRAINT fk_live_answer_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_live_answer_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE game_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NULL,
  mode ENUM('individu','tim') DEFAULT 'individu',
  status ENUM('running','finished','abandoned') DEFAULT 'running',
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  score INT DEFAULT 0,
  total_correct INT DEFAULT 0,
  total_wrong INT DEFAULT 0,
  duration_seconds INT DEFAULT 0,
  CONSTRAINT fk_session_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_session_room FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  user_id INT NOT NULL,
  question_id INT NOT NULL,
  answer TEXT NULL,
  is_correct TINYINT(1) NULL,
  score INT DEFAULT 0,
  teacher_score INT NULL,
  feedback TEXT NULL,
  status ENUM('saved','pending','reviewed') DEFAULT 'saved',
  autosaved_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_session_question(session_id, question_id),
  CONSTRAINT fk_response_session FOREIGN KEY(session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_response_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_response_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE academic_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL,
  score INT NOT NULL DEFAULT 0,
  total_questions INT NOT NULL DEFAULT 0,
  total_correct INT NOT NULL DEFAULT 0,
  total_wrong INT NOT NULL DEFAULT 0,
  status ENUM('running','finished') NOT NULL DEFAULT 'finished',
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_academic_user_type(user_id, assessment_type),
  CONSTRAINT fk_academic_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE academic_assessment_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT NOT NULL,
  user_id INT NOT NULL,
  question_id INT NOT NULL,
  answer TEXT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  score INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_academic_answer_question(question_id),
  CONSTRAINT fk_academic_answer_assessment FOREIGN KEY(assessment_id) REFERENCES academic_assessments(id) ON DELETE CASCADE,
  CONSTRAINT fk_academic_answer_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_academic_answer_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_learning_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  pretest_completed_at DATETIME NULL,
  learn_completed_at DATETIME NULL,
  drag_completed_at DATETIME NULL,
  live_game_completed_at DATETIME NULL,
  posttest_completed_at DATETIME NULL,
  remedial_completed_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_learning_progress_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE remedial_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reason TEXT NOT NULL,
  recommended_material TEXT NOT NULL,
  status ENUM('open','done') NOT NULL DEFAULT 'open',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  INDEX idx_remedial_user_status(user_id, status),
  CONSTRAINT fk_remedial_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_id INT NOT NULL,
  mood VARCHAR(50) NOT NULL,
  reflection TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eval_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_eval_session FOREIGN KEY(session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users(name, username, password, role, status, avatar_key) VALUES
('Guru PABETAS', 'guru', '$2y$12$V7tqCGTl163n44bX5S7dWeYIDoabnwbNgEYqVg5yEsK7KKo6dh34.', 'guru', 1, 'book'),
('Siswa Demo', 'siswa', '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', 'murid', 1, 'rocket'),
('Alya Putri', 'alya', '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', 'murid', 1, 'star'),
('Bima Satria', 'bima', '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', 'murid', 1, 'robot'),
('Citra Lestari', 'citra', '$2y$12$AoUblxp6gEWnSlMOak92lOOehBUWE8H8H.pZLivnE.NQOWBrzD0hW', 'murid', 1, 'cat');

INSERT INTO students(user_id, nis, class_name, gender) VALUES
(2,'003','III A','L'),(3,'004','III A','P'),(4,'005','III A','L'),(5,'006','III A','P');

INSERT INTO learning_materials(title, content, step_order, media_type, media_url, drag_json, status, created_by) VALUES
('Mengenal Urutan Satuan Panjang', 'Urutan satuan panjang dari terbesar ke terkecil adalah km, hm, dam, m, dm, cm, dan mm. Ingat urutannya seperti anak tangga.', 1, 'text', '', '["km","hm","dam","m","dm","cm","mm"]', 1, 1),
('Aturan Turun dan Naik Tangga', 'Jika turun satu tingkat, nilai dikali 10. Jika naik satu tingkat, nilai dibagi 10. Turun dua tingkat berarti dikali 100. Naik dua tingkat berarti dibagi 100.', 2, 'text', '', '["turun=dikali","naik=dibagi"]', 1, 1),
('Contoh Konversi', 'Contoh: 3 km = 3000 m karena dari km ke m turun tiga tingkat. Contoh lain: 600 cm = 6 m karena dari cm ke m naik dua tingkat.', 3, 'text', '', '', 1, 1);

INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by) VALUES
('individu','multiple','3 km = ... m','["3000 m","300 m","30 m","3 m"]','3000 m',10,1,1),
('individu','multiple','600 cm = ... m','["6 m","60 m","6000 m","0 m"]','6 m',10,1,1),
('individu','short','45 m = ... cm','[]','4500 cm',10,1,1),
('individu','short','8 hm = ... dm','[]','8000 dm',10,1,1),
('individu','essay','Jelaskan mengapa turun tangga satuan berarti dikali 10.','[]','manual',20,1,1),
('tim','multiple','7.000 mm = ... m','["7 m","70 m","700 m","7000 m"]','7 m',10,1,1),
('tim','short','5 dam = ... dm','[]','500 dm',10,1,1),
('individu','multiple','1 m = ... cm','["10 cm","100 cm","1000 cm","1 cm"]','100 cm',10,1,1),
('individu','multiple','2 dam = ... m','["2 m","20 m","200 m","2000 m"]','20 m',10,1,1),
('individu','multiple','9 m = ... dm','["90 dm","900 dm","9 dm","0 dm"]','90 dm',10,1,1),
('individu','short','4 km = ... m','[]','4000 m',10,1,1),
('individu','short','3000 mm = ... m','[]','3 m',10,1,1),
('tim','multiple','2 m = ... cm','["20 cm","200 cm","2000 cm","2 cm"]','200 cm',10,1,1),
('tim','multiple','9 hm = ... m','["90 m","900 m","9000 m","9 m"]','900 m',10,1,1),
('tim','short','4 km = ... dam','[]','400 dam',10,1,1);


-- Tambahan bank soal agar Live Game 10 soal tidak kurang.
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','multiple','5 m = ... cm','["50 cm","500 cm","5000 cm","5 cm"]','500 cm',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='5 m = ... cm');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','multiple','6 km = ... dam','["60 dam","600 dam","6000 dam","6 dam"]','600 dam',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='6 km = ... dam');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','multiple','12 dm = ... cm','["12 cm","120 cm","1200 cm","1 cm"]','120 cm',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='12 dm = ... cm');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','short','70 dm = ... m','[]','7 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='70 dm = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','short','900 cm = ... m','[]','9 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='900 cm = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'individu','short','15 m = ... mm','[]','15000 mm',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='15 m = ... mm');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','multiple','5 m = ... mm','["50 mm","500 mm","5000 mm","5 mm"]','5000 mm',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='5 m = ... mm');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','multiple','3 hm = ... m','["30 m","300 m","3000 m","3 m"]','300 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='3 hm = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','multiple','8 dam = ... m','["8 m","80 m","800 m","8000 m"]','80 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='8 dam = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','short','60 dm = ... m','[]','6 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='60 dm = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','short','700 cm = ... m','[]','7 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='700 cm = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','short','2 km = ... m','[]','2000 m',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='2 km = ... m');
INSERT INTO questions(mode,type,prompt,options_json,answer_key,score,status,created_by)
SELECT 'tim','short','30 m = ... cm','[]','3000 cm',10,1,1
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE prompt='30 m = ... cm');
-- PABETAS V3 Upgrade
-- Fitur: kelompok bank soal, template kuis/pretest/posttest/remedial, alur belajar guru, materi tabel interaktif, laporan rekap live PDF/Excel.

-- MySQL Aiven fix: group_label already exists in CREATE TABLE questions
-- MySQL Aiven fix: difficulty already exists in CREATE TABLE questions

CREATE TABLE IF NOT EXISTS assessment_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL DEFAULT 'quiz',
  description TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_template_type_status(assessment_type,status),
  CONSTRAINT fk_template_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_template_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  question_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 1,
  UNIQUE KEY uq_template_question(template_id, question_id),
  CONSTRAINT fk_template_question_template FOREIGN KEY(template_id) REFERENCES assessment_templates(id) ON DELETE CASCADE,
  CONSTRAINT fk_template_question_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS learning_flow_steps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  step_order INT NOT NULL DEFAULT 1,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  action_type ENUM('pretest','material','live_game','posttest','remedial','quiz','reflection','custom') NOT NULL DEFAULT 'custom',
  action_payload TEXT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_drag_games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NULL,
  title VARCHAR(150) NOT NULL,
  instruction TEXT NULL,
  items_json TEXT NOT NULL,
  correct_json TEXT NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT fk_drag_material FOREIGN KEY(material_id) REFERENCES learning_materials(id) ON DELETE SET NULL,
  CONSTRAINT fk_drag_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE questions SET group_label = CASE
  WHEN mode='individu' AND type='essay' THEN 'Individu Esai'
  WHEN mode='individu' AND type='multiple' THEN 'Individu Pilihan Ganda'
  WHEN mode='individu' AND type='short' THEN 'Individu Jawaban Singkat'
  WHEN mode='tim' AND type='essay' THEN 'Tim Esai'
  WHEN mode='tim' AND type='multiple' THEN 'Tim Pilihan Ganda'
  WHEN mode='tim' AND type='short' THEN 'Tim Jawaban Singkat'
  ELSE 'Umum'
END
WHERE group_label='Umum' OR group_label='';

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 1,'Pretest','Tes awal untuk mengukur pemahaman sebelum pembelajaran.','pretest','',1,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps LIMIT 1);

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 2,'Pembelajaran Papan Belajar','Siswa membaca materi satuan panjang, melihat tabel ke bawah, dan mencoba contoh soal.','material','',1,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE step_order=2);

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 3,'Live Game','Siswa masuk game seperti Quizizz memakai kode dari guru.','live_game','',1,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE step_order=3);

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 4,'Posttest','Tes akhir untuk melihat peningkatan nilai setelah belajar.','posttest','',1,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE step_order=4);

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 5,'Remedial Otomatis','Latihan penguatan bagi siswa yang belum tuntas.','remedial','',0,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE step_order=5);

INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 6,'Refleksi Belajar','Murid memilih perasaan hari ini dan menuliskan kendala belajar.','reflection','',0,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE step_order=6);

INSERT INTO assessment_templates(title,assessment_type,description,status,created_by)
SELECT 'Pretest Satuan Panjang','pretest','Template pretest awal PABETAS.',1,(SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM assessment_templates WHERE assessment_type='pretest');

INSERT INTO assessment_templates(title,assessment_type,description,status,created_by)
SELECT 'Posttest Satuan Panjang','posttest','Template posttest akhir PABETAS.',1,(SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM assessment_templates WHERE assessment_type='posttest');

INSERT INTO assessment_templates(title,assessment_type,description,status,created_by)
SELECT 'Remedial Konversi Naik Turun','remedial','Template remedial untuk penguatan konversi satuan.',1,(SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM assessment_templates WHERE assessment_type='remedial');

INSERT INTO assessment_template_questions(template_id, question_id, sort_order)
SELECT t.id, q.id, ROW_NUMBER() OVER (ORDER BY q.id)
FROM assessment_templates t
JOIN questions q ON q.mode='individu' AND q.status=1 AND q.type IN ('multiple','short')
WHERE t.assessment_type IN ('pretest','posttest','remedial')
AND NOT EXISTS (SELECT 1 FROM assessment_template_questions tq WHERE tq.template_id=t.id)
LIMIT 30;


ALTER TABLE academic_assessments MODIFY assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL;

INSERT INTO learning_materials(title,content,step_order,media_type,media_url,drag_json,status,created_by)
SELECT 'Strategi Membaca Soal Konversi','Baca satuan asal dan satuan tujuan. Hitung jumlah langkah pada tangga satuan. Jika bergerak ke kanan atau turun menuju satuan lebih kecil, kalikan 10 setiap langkah. Jika bergerak ke kiri atau naik menuju satuan lebih besar, bagi 10 setiap langkah.',4,'text','', '["asal","tujuan","langkah","operasi","hasil"]',1,(SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM learning_materials WHERE title='Strategi Membaca Soal Konversi');

INSERT INTO learning_materials(title,content,step_order,media_type,media_url,drag_json,status,created_by)
SELECT 'Cara Mengecek Jawaban','Cek kewajaran jawaban. Dari km ke m nilainya harus lebih besar. Dari mm ke m nilainya harus lebih kecil. Jika arah jawaban tidak sesuai, kemungkinan operasi hitungnya tertukar.',5,'text','', '["km ke m lebih besar","mm ke m lebih kecil","turun kali","naik bagi"]',1,(SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM learning_materials WHERE title='Cara Mengecek Jawaban');


-- PABETAS V4 Upgrade
-- Fitur: menu Soal Esai siswa, Paket Esai guru, pilihan soal manual/otomatis untuk Live Game dan Kuis.
ALTER TABLE academic_assessments MODIFY assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL;

CREATE TABLE IF NOT EXISTS assessment_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL DEFAULT 'quiz',
  description TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_template_type_status(assessment_type,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_template_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  question_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 1,
  UNIQUE KEY uq_template_question(template_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contoh paket esai hanya dibuat jika belum ada template esai.
INSERT INTO assessment_templates(title,assessment_type,description,status,created_by,created_at)
SELECT 'Esai Pemahaman Tangga Satuan','quiz','[ESAI] Jawab dengan kalimat sederhana sesuai pemahamanmu.',1,1,NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM assessment_templates t
  JOIN assessment_template_questions tq ON tq.template_id=t.id
  JOIN questions q ON q.id=tq.question_id
  WHERE t.assessment_type='quiz' AND q.type='essay'
);

SET @essay_template_id := (SELECT id FROM assessment_templates WHERE title='Esai Pemahaman Tangga Satuan' ORDER BY id DESC LIMIT 1);
INSERT IGNORE INTO assessment_template_questions(template_id,question_id,sort_order)
SELECT @essay_template_id, q.id, @rownum:=@rownum+1
FROM questions q, (SELECT @rownum:=0) r
WHERE q.status=1 AND q.type='essay' AND @essay_template_id IS NOT NULL
ORDER BY q.id ASC
LIMIT 5;
-- PABETAS V6 Upgrade
-- Fokus: Mode tim 1 device/kelompok, peran siswa ditentukan guru, timer tim, refleksi, visual materi video, dan audio konteks.

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE rooms ADD COLUMN question_seconds INT NOT NULL DEFAULT 30 AFTER status', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rooms' AND COLUMN_NAME = 'question_seconds');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE rooms ADD COLUMN team_question_count INT NOT NULL DEFAULT 5 AFTER question_seconds', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rooms' AND COLUMN_NAME = 'team_question_count');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE teams ADD COLUMN members_json TEXT NULL AFTER name', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'members_json');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE game_sessions ADD COLUMN team_id INT NULL AFTER room_id', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_sessions' AND COLUMN_NAME = 'team_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS reflection_prompts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  question_text TEXT NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS learning_reflections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  prompt_id INT NULL,
  mood VARCHAR(40) NOT NULL,
  difficulty_level ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang',
  obstacle_text TEXT NULL,
  teacher_note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_reflection_user(user_id),
  CONSTRAINT fk_reflection_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reflection_prompt FOREIGN KEY(prompt_id) REFERENCES reflection_prompts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO reflection_prompts(title, question_text, status, created_by)
SELECT 'Refleksi Belajar Hari Ini', 'Bagian mana yang masih membuatmu bingung setelah belajar satuan panjang?', 1, (SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM reflection_prompts WHERE status=1 LIMIT 1);


-- action_type enum sudah memuat reflection sejak CREATE TABLE, jadi ALTER ini tidak diperlukan lagi.
INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 7,'Refleksi Belajar','Murid memilih perasaan hari ini dan menuliskan kendala belajar.','reflection','',0,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE action_type='reflection');
