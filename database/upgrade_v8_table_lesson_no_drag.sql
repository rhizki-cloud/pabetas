-- Patch V8: Papan Belajar tabel interaktif dan penghapusan menu game drag & drop.
-- Jalankan satu kali pada database lama jika sebelumnya ada alur drag.

UPDATE learning_flow_steps
SET status = 0
WHERE action_type = 'drag';

-- Susun ulang alur standar jika masih memakai data lama.
UPDATE learning_flow_steps SET step_order = 1 WHERE action_type = 'pretest';
UPDATE learning_flow_steps SET step_order = 2 WHERE action_type = 'material';
UPDATE learning_flow_steps SET step_order = 3 WHERE action_type = 'live_game';
UPDATE learning_flow_steps SET step_order = 4 WHERE action_type = 'posttest';
UPDATE learning_flow_steps SET step_order = 5 WHERE action_type = 'remedial';
UPDATE learning_flow_steps SET step_order = 6 WHERE action_type = 'reflection';
