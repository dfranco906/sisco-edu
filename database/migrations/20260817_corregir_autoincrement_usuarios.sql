-- Corrige las altas administrativas de usuarios.
-- El registro histórico con id_usuario = 0 se conserva sin renumerarlo.
SET @SISCO_OLD_SQL_MODE = @@SESSION.SQL_MODE;
SET SESSION SQL_MODE = CONCAT_WS(',', NULLIF(@@SESSION.SQL_MODE, ''), 'NO_AUTO_VALUE_ON_ZERO');

ALTER TABLE usuarios
    MODIFY id_usuario INT NOT NULL AUTO_INCREMENT;

SET SESSION SQL_MODE = @SISCO_OLD_SQL_MODE;
