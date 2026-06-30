-- PABETAS V6 Upgrade
-- Fokus: Mode tim 1 device/kelompok, peran siswa ditentukan guru, timer tim, refleksi, visual materi video, dan audio konteks.

ALTER TABLE rooms ADD COLUMN IF NOT EXISTS question_seconds INT NOT NULL DEFAULT 30 AFTER status;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS team_question_count INT NOT NULL DEFAULT 5 AFTER question_seconds;
ALTER TABLE teams ADD COLUMN IF NOT EXISTS members_json TEXT NULL AFTER name;
ALTER TABLE game_sessions ADD COLUMN IF NOT EXISTS team_id INT NULL AFTER room_id;

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

INSERT INTO learning_materials(title,content,step_order,media_type,media_url,drag_json,status,created_by)
SELECT 'Video Pembelajaran Mengubah Satuan Panjang', 'Tonton video, amati tabel satuan, lalu coba ubah satuan dengan cara menambah nol saat turun dan mengurangi nol saat naik. Video ini dipakai sebagai referensi pembelajaran visual siswa.', 0, 'video', 'https://www.youtube.com/embed/wvlY48_al9Y', '["Tonton","Amati","Coba","Cek"]', 1, (SELECT id FROM users WHERE role='guru' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM learning_materials WHERE media_url LIKE '%wvlY48_al9Y%');

ALTER TABLE learning_flow_steps MODIFY action_type ENUM('pretest','material','drag','live_game','posttest','remedial','quiz','reflection','custom') NOT NULL DEFAULT 'custom';
INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status)
SELECT 7,'Refleksi Belajar','Murid memilih perasaan hari ini dan menuliskan kendala belajar.','reflection','',0,1
WHERE NOT EXISTS (SELECT 1 FROM learning_flow_steps WHERE action_type='reflection');
