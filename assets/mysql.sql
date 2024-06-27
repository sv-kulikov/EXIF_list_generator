-- This is the set of commands to create a MySQL database, import the data
-- and play with some analytic queries.

-- WARNING! DANGER HERE!
-- DROP SCHEMA IF EXISTS `elg`;

-- Create the schema (database).
CREATE SCHEMA `elg` DEFAULT CHARACTER SET utf8 ;

-- WARNING! DANGER HERE!
DROP TABLE IF EXISTS `photos`;

-- Create the table.
CREATE TABLE `photos` (
    `name` VARCHAR(255),
    `fullName` TEXT,
    `size` BIGINT,
    `sizeHumanReadable` VARCHAR(100),
    `mimeType` VARCHAR(50),
    `height` INT,
    `width` INT,
    `dateY` INT,
    `dateM` INT,
    `dateD` INT,
    `dateH` INT,
    `dateI` INT,
    `dateS` INT,
    `cameraVendor` VARCHAR(255),
    `cameraModel` VARCHAR(255),
    `lens` VARCHAR(255),
    `lensName` VARCHAR(255),
    `lensNameCleared` VARCHAR(255),
    `software` VARCHAR(255),
    `exposureTimeRaw` VARCHAR(50),
    `exposureTime` DECIMAL(25,15),
    `fNumberRaw` VARCHAR(50),
    `fNumber` DECIMAL(25,15),
    `iso` VARCHAR(50),
    `focalLengthRaw` VARCHAR(50),
    `focalLength` DECIMAL(25,15),
    `shutterSpeedValueRaw` VARCHAR(50),
    `shutterSpeedValue` DECIMAL(25,15),
    `apertureValueRaw` VARCHAR(50),
    `apertureValue` DECIMAL(25,15),
    `exposureBiasValueRaw` VARCHAR(50),
    `exposureBiasValue` DECIMAL(25,15),
    `flashRaw` VARCHAR(50),
    `flash` VARCHAR(255),
    `exposureProgramRaw` INT,
    `exposureProgram` VARCHAR(50),
    `meteringModeRaw` INT,
    `meteringMode` VARCHAR(50),
    `whiteBalanceRaw` INT,
    `whiteBalance` VARCHAR(50),
    `exposureModeRaw` INT,
    `exposureMode` VARCHAR(50),
    `colorSpaceRaw` VARCHAR(50),
    `colorSpace` VARCHAR(50),
    `contrast` VARCHAR(50),
    `saturation` VARCHAR(50),
    `sharpness` VARCHAR(50),
    `gpsLatitude` VARCHAR(50),
    `gpsLongitude` VARCHAR(50),
    `gpsAltitude` VARCHAR(50),
    `gpsDateTime` VARCHAR(50),
    `exifVersion` VARCHAR(50)
);

-- Allow local data import.
SET global local_infile = 1;

-- If you are using MySQL Workbench:
-- Edit the connection, on the Connection tab, go to the
-- 'Advanced' sub-tab, and in the 'Others:' box add the line 'OPT_LOCAL_INFILE=1'.
-- This should allow a client using the Workbench to run LOAD DATA INFILE as usual.

-- Import the data.
LOAD DATA LOCAL INFILE 'C:/PATH/elg.csv'
INTO TABLE `photos`
FIELDS TERMINATED BY ','
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Check the result.
SELECT COUNT(*) FROM `photos`;

-- Get top 10 cameras with photos count.
SELECT `cameraModel`,
       COUNT(*) AS `photos_count`,
       DENSE_RANK() OVER (PARTITION BY NULL ORDER BY COUNT(*) DESC) AS `rank`
FROM `photos`
GROUP BY `cameraModel`
ORDER BY `photos_count` DESC
    LIMIT 10;

-- Get top 10 cameras with photos count by year.
SELECT `year`, `cameraModel`, `photos_count`, `rank`
FROM (
         SELECT
             `dateY` AS `year`,
             `cameraModel`,
             COUNT(*) AS `photos_count`,
             DENSE_RANK() OVER (PARTITION BY `dateY` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `year`, `cameraModel`
     ) `ranked`
WHERE `ranked`.`rank` <= 10;


-- Get top lenses with photos count.
SELECT `lensName`, `photos_count`, `rank`
FROM (
         SELECT `lensName`,
                COUNT(*) AS `photos_count`,
                DENSE_RANK() OVER (ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `lensName`
     ) AS `ranked`
ORDER BY `photos_count` DESC;

-- Get top 10 lenses with photos count by year.
SELECT `year`, `lensName`, `photos_count`, `rank`
FROM (
         SELECT
             `dateY` AS `year`,
             `lensName`,
             COUNT(*) AS `photos_count`,
             RANK() OVER (PARTITION BY `dateY` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `year`, `lensName`
     ) AS `ranked`
WHERE `ranked`.`rank` <= 10
ORDER BY `year` ASC, `photos_count` DESC;

-- Get top 10 lenses by camera with photos count.
SELECT `cameraModel`, `lensName`, `photos_count`, `rank`
FROM (
         SELECT
             `cameraModel`,
             `lensName`,
             COUNT(*) AS `photos_count`,
             RANK() OVER (PARTITION BY `cameraModel` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `cameraModel`, `lensName`
     ) AS `ranked`
WHERE `ranked`.`rank` <= 10
ORDER BY `cameraModel` ASC, `photos_count` DESC;


-- Photos distribution by megapixels.
SELECT
    CASE
        WHEN (`height` * `width` / 1000000) < 5 THEN '<5 MP'
        WHEN (`height` * `width` / 1000000) BETWEEN 5 AND 10 THEN '05-10 MP'
        WHEN (`height` * `width` / 1000000) BETWEEN 10 AND 20 THEN '10-20 MP'
        WHEN (`height` * `width` / 1000000) BETWEEN 20 AND 30 THEN '20-30 MP'
        WHEN (`height` * `width` / 1000000) BETWEEN 30 AND 40 THEN '30-40 MP'
        WHEN (`height` * `width` / 1000000) BETWEEN 40 AND 50 THEN '40-50 MP'
        ELSE '50+ MP'
        END AS `megapixel_range`,
    COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `megapixel_range`
ORDER BY `megapixel_range`;

-- Most common ISO settings used.
SELECT `iso`, COUNT(*) AS `usage_count`
FROM `photos`
GROUP BY `iso`
ORDER BY `usage_count` DESC;

-- Top 10 ISO settings by camera.
SELECT `cameraModel`, `iso`, `usage_count`
FROM (
         SELECT
             `cameraModel`,
             `iso`,
             COUNT(*) AS `usage_count`,
             RANK() OVER (PARTITION BY `cameraModel` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `cameraModel`, `iso`
     ) AS ranked
WHERE ranked.`rank` <= 10
ORDER BY `cameraModel`, `usage_count` DESC;

-- Top 10 ISO settings by lens.
SELECT `lensName`, `iso`, `usage_count`
FROM (
         SELECT
             `lensName`,
             `iso`,
             COUNT(*) AS `usage_count`,
             RANK() OVER (PARTITION BY `lensName` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `lensName`, `iso`
     ) AS ranked
WHERE ranked.`rank` <= 10
ORDER BY `lensName`, `usage_count` DESC;

-- Top 10 ISO settings by camera and lens.
SELECT `cameraModel`, `lensName`, `iso`, `usage_count`
FROM (
         SELECT
             `cameraModel`,
             `lensName`,
             `iso`,
             COUNT(*) AS `usage_count`,
             RANK() OVER (PARTITION BY `cameraModel`, `lensName` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `cameraModel`, `lensName`, `iso`
     ) AS ranked
WHERE ranked.`rank` <= 10
ORDER BY `cameraModel`, `lensName`, `usage_count` DESC;

-- Top 10 ISO settings by year.
SELECT `dateY` AS `year`, `iso`, `usage_count`
FROM (
         SELECT
             `dateY`,
             `iso`,
             COUNT(*) AS `usage_count`,
             RANK() OVER (PARTITION BY `dateY` ORDER BY COUNT(*) DESC) AS `rank`
         FROM `photos`
         GROUP BY `dateY`, `iso`
     ) AS ranked
WHERE ranked.`rank` <= 10
ORDER BY `year`, `usage_count` DESC;

-- Average exposure settings by camera model.
SELECT `cameraModel`, AVG(`exposureTime`) AS `avg_exposureTime`, AVG(`fNumber`) AS `avg_fNumber`
FROM `photos`
GROUP BY `cameraModel`;

-- Distribution of photos by color space.
SELECT `colorSpace`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `colorSpace`;

-- Count of photos taken with flash versus without.
SELECT `flash`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `flash`;

-- Most frequently used focal lengths.
SELECT `focalLength`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `focalLength`
ORDER BY `photos_count` DESC;

-- Most frequently used typical focal lengths.
SELECT
    `focal_length_range`,
    COUNT(*) AS `photos_count`
FROM (
         SELECT
             CASE
                 WHEN `focalLength` BETWEEN 0 AND 24 THEN '0-24mm (Ultra Wide)'
                 WHEN `focalLength` BETWEEN 24 AND 35 THEN '24-35mm (Wide)'
                 WHEN `focalLength` BETWEEN 35 AND 70 THEN '35-70mm (Standard)'
                 WHEN `focalLength` BETWEEN 70 AND 200 THEN '70-200mm (Telephoto)'
                 WHEN `focalLength` BETWEEN 200 AND 300 THEN '200-300mm (Super Telephoto)'
                 WHEN `focalLength` BETWEEN 300 AND 500 THEN '300-500mm (Super Telephoto)'
                 WHEN `focalLength` > 500 THEN '500mm+ (Extreme Telephoto)'
                 ELSE 'Other'
                 END AS `focal_length_range`,
             `focalLength`
         FROM `photos`
     ) AS `focal_ranges`
GROUP BY `focal_length_range`
ORDER BY COUNT(*) DESC;

-- Frequency of different exposure programs.
SELECT `exposureProgram`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `exposureProgram`;

-- Photos count per month across all years.
SELECT `dateM` AS `month`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `month`
ORDER BY `month`;

-- Frequency of using various metering modes.
SELECT `meteringMode`, COUNT(*) AS `photos_count`
FROM `photos`
GROUP BY `meteringMode`;

-- Average shutter speed and aperture values for photos by year.
SELECT `dateY` AS `year`, AVG(`shutterSpeedValue`) AS `avg_shutterSpeed`, AVG(`apertureValue`) AS `avg_aperture`
FROM `photos`
GROUP BY `year`;