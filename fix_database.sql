-- Fix database schema for HR system

-- Add CreatedBy and CreatedDate to tblemploymenttype
ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER BranchID;
ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- Add CreatedBy and CreatedDate to tblgroup if missing
ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER Description;
ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- Add CreatedBy and CreatedDate to tbljobgrade if missing
ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER Description;
ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- Add CreatedBy and CreatedDate to tblsection if missing
ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER ParentID;
ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- Add CreatedBy and CreatedDate to tbljobtitle if missing
ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER ParentID;
ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- Add CreatedBy and CreatedDate to holidays if missing
ALTER TABLE holidays ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER End_date;
ALTER TABLE holidays ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy;
ALTER TABLE holidays ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate;

-- No changes needed for tbshift as it already has CreatedBy, CreatedDate, and TotalworkHour
