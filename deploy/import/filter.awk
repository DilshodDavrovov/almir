# Strip secondary indexes / FKs of drug_reports, drug_reports_s, drug_reports_p from a dbForge dump stream.
# Removed statements are copied to removed_ddl.sql for later re-creation.
BEGIN { skip=0; held=""; tbl=""; out="removed_ddl.sql" }
NR <= 8100 {
  sub(/\r$/, "")
  if (skip == 1) {
    if ($0 ~ /^ADD (UNIQUE )?INDEX / || $0 ~ /^ADD CONSTRAINT /) {
      print alter_line > out; print $0 > out
      if ($0 ~ /;[ \t]*$/) skip = 0; else skip = 2
      next
    }
    print alter_line; skip = 0
  }
  if (skip == 2) { print $0 > out; if ($0 ~ /;[ \t]*$/) skip = 0; next }
  if ($0 ~ /^ALTER TABLE drug_reports(_[sp])?$/) { alter_line = $0; skip = 1; next }
  if ($0 ~ /^CREATE TABLE /) { tbl = $3 }
  if (held != "") {
    if ($0 ~ /^  UNIQUE INDEX uk_id \(id\)$/ && tbl ~ /^drug_reports(_[sp])?$/) { print "  PRIMARY KEY (id)"; print "-- " tbl ": UNIQUE INDEX uk_id (id) removed" > out; held = ""; next }
    print held; held = ""
  }
  if ($0 == "  PRIMARY KEY (id)," && tbl ~ /^drug_reports(_[sp])?$/) { held = $0; next }
  print; next
}
{ print }
