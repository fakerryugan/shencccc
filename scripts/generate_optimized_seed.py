import json
import os
import sys
from datetime import datetime

def format_sql_value(val):
    if val is None:
        return "NULL"
    if isinstance(val, bool):
        return "1" if val else "0"
    if isinstance(val, (int, float)):
        return str(val)
    if isinstance(val, (dict, list)):
        return "'" + json.dumps(val, ensure_ascii=False).replace("'", "''").replace("\\", "\\\\") + "'"
    # String
    return "'" + str(val).replace("'", "''").replace("\\", "\\\\") + "'"

def get_mysql_datetime(timestamp_dict):
    if not timestamp_dict:
        return "NULL"
    iso_str = ""
    if isinstance(timestamp_dict, str):
        iso_str = timestamp_dict
    elif "__firestoreTimestamp" in timestamp_dict:
        iso_str = timestamp_dict['__firestoreTimestamp']
    elif "_seconds" in timestamp_dict:
        dt = datetime.fromtimestamp(int(timestamp_dict["_seconds"]))
        iso_str = dt.isoformat()
    else:
        return "NULL"
    
    cleaned = iso_str.replace("Z", "").replace("T", " ")
    if "." in cleaned:
        cleaned = cleaned.split(".")[0]
    return f"'{cleaned}'"

def main():
    json_path = "/home/fatkur/hrdsencha/sencha-backup-lamaran-sencha-2026-05-31-13-49-36.json"
    sql_out_path = "/home/fatkur/hrdsencha/database/seed_applicants.sql"

    if not os.path.exists(json_path):
        print(f"Error: JSON file not found at {json_path}")
        sys.exit(1)

    print("Loading JSON backup file...")
    with open(json_path, "r", encoding="utf-8") as f:
        backup = json.load(f)

    collections = backup.get("collections", {})
    
    # 1. Start writing SQL
    print("Generating seed SQL file...")
    with open(sql_out_path, "w", encoding="utf-8") as f:
        f.write("-- Optimized Seed SQL for Sencha Recruitment MySQL\n")
        f.write("SET FOREIGN_KEY_CHECKS = 0;\n")
        f.write("TRUNCATE TABLE applicant_files;\n")
        f.write("TRUNCATE TABLE applicants;\n")
        f.write("TRUNCATE TABLE fs_documents;\n")
        f.write("SET FOREIGN_KEY_CHECKS = 1;\n\n")

        # Process standard NoSQL collections first (positions, activity_log, etc.)
        for col_name, items in collections.items():
            if col_name == "applicants":
                continue # Handled in normalized table below
            
            if isinstance(items, dict) and "id" in items and "data" in items:
                items = [items]

            if not isinstance(items, list):
                continue

            print(f"Processing collection: {col_name} ({len(items)} items)")
            
            # Bulk inserts for fs_documents
            f.write(f"-- Seed data for {col_name}\n")
            doc_rows = []
            for item in items:
                doc_id = item.get("id", "")
                if not doc_id:
                    continue
                path = f"{col_name}/{doc_id}"
                parent_path = col_name
                data_json = json.dumps(item.get("data", {}), ensure_ascii=False)
                
                doc_rows.append(f"({format_sql_value(path)}, {format_sql_value(parent_path)}, {format_sql_value(data_json)}, NOW())")
            
            if doc_rows:
                # Insert in chunks of 100 for safety
                for i in range(0, len(doc_rows), 100):
                    chunk = doc_rows[i:i+100]
                    f.write(f"INSERT INTO fs_documents (path, parent_path, data, updated_at) VALUES\n" + ",\n".join(chunk) + "\nON DUPLICATE KEY UPDATE data=VALUES(data), updated_at=NOW();\n\n")

        # 2. Process and Normalize Applicants Collection
        applicants = collections.get("applicants", [])
        if isinstance(applicants, dict) and "id" in applicants and "data" in applicants:
            applicants = [applicants]

        print(f"Normalizing applicants collection ({len(applicants)} items)...")
        f.write("-- Seed data for normalized applicants and applicant_files\n")
        
        app_rows = []
        file_rows = []

        for item in applicants:
            app_id = item.get("id", "")
            if not app_id:
                continue

            data = item.get("data", {})
            
            # Extract main fields
            nama = data.get("nama")
            nama_normalized = data.get("namaNormalized")
            whatsapp = data.get("whatsapp")
            whatsapp_normalized = data.get("whatsappNormalized")
            tanggal_lahir = data.get("tanggalLahir")
            umur_saat_input = data.get("umurSaatInput")
            masih_bekerja = data.get("masihBekerja", False)
            posisi = data.get("posisi")
            posisi_list = data.get("posisiList", [])
            status = data.get("status", "baru")
            source = data.get("source")
            undangan_by_posisi = data.get("undanganByPosisi", {})
            access_token = data.get("accessToken")
            cv_mode = data.get("cvMode")
            catatan = data.get("catatan")
            catatan_list = data.get("catatanList", [])
            
            created_at = get_mysql_datetime(data.get("createdAt"))
            updated_at = get_mysql_datetime(data.get("updatedAt"))

            # Extract heavy fields
            cv_file = data.get("cvFile")
            photos = data.get("photos", [])

            # Prepare applicant metadata insert
            app_rows.append(
                f"({format_sql_value(app_id)}, {format_sql_value(nama)}, {format_sql_value(nama_normalized)}, "
                f"{format_sql_value(whatsapp)}, {format_sql_value(whatsapp_normalized)}, {format_sql_value(tanggal_lahir)}, "
                f"{format_sql_value(umur_saat_input)}, {format_sql_value(masih_bekerja)}, {format_sql_value(posisi)}, "
                f"{format_sql_value(posisi_list)}, {format_sql_value(status)}, {format_sql_value(source)}, "
                f"{format_sql_value(undangan_by_posisi)}, {format_sql_value(access_token)}, {format_sql_value(cv_mode)}, "
                f"{format_sql_value(catatan)}, {format_sql_value(catatan_list)}, {created_at}, {updated_at})"
            )

            # Prepare applicant files insert (only if we have heavy content)
            if cv_file or photos:
                file_rows.append(
                    f"({format_sql_value(app_id)}, {format_sql_value(cv_file)}, {format_sql_value(photos)})"
                )

        # Write normalized applicant inserts
        if app_rows:
            for i in range(0, len(app_rows), 50):
                chunk = app_rows[i:i+50]
                f.write(
                    "INSERT INTO applicants (id, nama, nama_normalized, whatsapp, whatsapp_normalized, tanggal_lahir, "
                    "umur_saat_input, masih_bekerja, posisi, posisi_list, status, source, undangan_by_posisi, "
                    "access_token, cv_mode, catatan, catatan_list, created_at, updated_at) VALUES\n" + 
                    ",\n".join(chunk) + "\nON DUPLICATE KEY UPDATE nama=VALUES(nama), status=VALUES(status), updated_at=NOW();\n\n"
                )

        # Write heavy files inserts
        if file_rows:
            for i in range(0, len(file_rows), 10): # Smaller chunk due to massive base64 CV payloads
                chunk = file_rows[i:i+10]
                f.write(
                    "INSERT INTO applicant_files (applicant_id, cv_file, photos) VALUES\n" +
                    ",\n".join(chunk) + "\nON DUPLICATE KEY UPDATE cv_file=VALUES(cv_file), photos=VALUES(photos);\n\n"
                )

    print(f"Successfully generated normalized SQL seed file at {sql_out_path}!")

if __name__ == "__main__":
    main()
