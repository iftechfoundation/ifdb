-- migrate:up
ALTER TABLE games_history
  ADD COLUMN spam TINYINT(1) NOT NULL DEFAULT 0;

-- migrate:down
ALTER TABLE games_history
  DROP COLUMN spam;
