"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function AdminUsersPage() {
  return (
    <PortalShell title="User Management" role="admin" links={[
      { href: "/admin", label: "Overview" }, { href: "/admin/users", label: "User Management" },
      { href: "/admin/facilities", label: "Facilities & Schedules" }, { href: "/admin/logs", label: "Login Logs" },
    ]}>
      <ResourcePanel resource="admins" title="Administrators" columns={["admin_id", "lastname", "firstname", "email"]} fields={["admin_id", "role_id", "lastname", "firstname", "mi", "email", "password"]} />
      <ResourcePanel resource="faculty" title="Faculty" columns={["employee_id", "lastname", "firstname", "email", "rfid_tag"]} fields={["employee_id", "role_id", "lastname", "firstname", "mi", "email", "password", "rfid_tag"]} />
      <ResourcePanel resource="professors" title="Professors" columns={["employee_id", "lastname", "firstname", "email", "rfid_tag"]} fields={["employee_id", "role_id", "lastname", "firstname", "mi", "email", "password", "rfid_tag"]} />
      <ResourcePanel resource="students" title="Students" columns={["student_id", "lastname", "firstname", "email", "section_id"]} fields={["student_id", "role_id", "lastname", "firstname", "mi", "email", "rfid_tag", "section_id"]} />
      <ResourcePanel resource="sections" title="Sections" columns={["section_name", "section_level"]} fields={["section_name", "section_level"]} />
      <ResourcePanel resource="subjects" title="Subjects" columns={["subject_code", "subject_name", "prof_user_id"]} fields={["subject_code", "subject_name", "prof_user_id"]} />
    </PortalShell>
  );
}
