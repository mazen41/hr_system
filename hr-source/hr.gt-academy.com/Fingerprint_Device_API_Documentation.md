# Fingerprint Device Integration – Technical API Documentation
Vision HR System

Version: 1.0  
Last Update: 2026-03-01

---

## 1. Overview
This document describes the technical integration between **Fingerprint Attendance Devices**
(ZKTeco / Anviz / Suprema) and the **Vision HR System**.

The system supports:
- Device registration
- Network-based communication (IP + Port)
- Attendance logs synchronization
- Branch-based device mapping

---

## 2. Database Schema (Aligned with System)

### 2.1 `fingerprint_devices`
Represents fingerprint devices added from:
Settings → Add New Device

| Column | Type | Description |
|------|------|------------|
| id | BIGINT | Primary key |
| name | VARCHAR | Device name |
| branch_id | BIGINT | Linked branch |
| vendor | VARCHAR | Device company/model |
| serial_number | VARCHAR | Unique device serial |
| ip_address | VARCHAR | Device IP |
| port | INT | Communication port |
| status | ENUM(active,inactive) | Device state |
| last_seen_at | TIMESTAMP | Last successful connection |
| created_at | TIMESTAMP | Created time |
| updated_at | TIMESTAMP | Updated time |

---

### 2.2 `fingerprint_device_users`
Maps device users to HR employees

| Column | Type | Description |
|------|------|------------|
| id | BIGINT | Primary key |
| device_id | BIGINT | Fingerprint device |
| employee_id | BIGINT | HR employee |
| device_user_id | VARCHAR | User ID inside device |
| created_at | TIMESTAMP | Created |

---

### 2.3 `attendance_logs`
Attendance punches synced from devices

| Column | Type | Description |
|------|------|------------|
| id | BIGINT | Primary key |
| device_id | BIGINT | Source device |
| employee_id | BIGINT | Linked employee |
| device_user_id | VARCHAR | User ID in device |
| punch_time | TIMESTAMP | Attendance time |
| punch_type | ENUM(IN,OUT,UNKNOWN) | Punch direction |
| verify_mode | ENUM(FINGER,CARD,PIN,FACE) | Auth method |
| hash | CHAR(40) | Deduplication hash |
| raw_payload | JSON | Original data |
| created_at | TIMESTAMP | Inserted |

Unique Index:
UNIQUE (hash)

---

## 3. Device Registration API

### Create Device
POST /api/hr/fingerprint-devices

```json
{
  "name": "Main Gate Device",
  "branch_id": 1,
  "vendor": "ZKTeco F18",
  "serial_number": "ZK-F18-001122",
  "ip_address": "192.168.1.201",
  "port": 4370,
  "status": "active"
}
```

Response:
```json
{
  "id": 10,
  "status": "active"
}
```

---

## 4. Device Connectivity Test

POST /api/hr/fingerprint-devices/{id}/test

Response:
```json
{
  "connected": true,
  "latency_ms": 42
}
```

---

## 5. Attendance Synchronization (Pull Model)

POST /api/hr/fingerprint-devices/{id}/sync-logs

```json
{
  "from": "2026-02-28 00:00:00",
  "to": "2026-03-01 23:59:59"
}
```

Response:
```json
{
  "pulled": 312,
  "inserted": 300,
  "duplicates": 12
}
```

---

## 6. Webhook Support (Optional)

POST /api/webhooks/fingerprint/{serial_number}

---

## 7. Supported Devices

| Vendor | Port |
|------|------|
| ZKTeco | 4370 |
| Anviz | 5010 |
| Suprema | 51211 |

---

End of Document
