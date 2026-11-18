"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function FacultyPage() {
  return (
    <PortalShell
      title="Faculty Overview"
      role="faculty"
      links={[
        { href: "/faculty", label: "Overview" },
        { href: "/faculty/schedule", label: "Schedules" },
        { href: "/faculty/rooms", label: "Rooms" },
      ]}
    >
      <p className="portal-intro">Monitor laboratory rooms, schedules, and the professor and student directories.</p>
      <ResourcePanel resource="rooms" title="Laboratory Rooms" columns={["room_number", "room_name", "status"]} fields={["room_number", "room_name", "status"]} />
      <ResourcePanel resource="schedules" title="Master Schedule" columns={["schedule_id", "prof_user_id", "subject_id", "section_id", "room_id", "schedule_day", "schedule_time"]} fields={["prof_user_id", "subject_id", "section_id", "room_id", "schedule_time", "schedule_end_time", "schedule_day"]} />
      <ResourcePanel resource="professors" title="Professors" columns={["employee_id", "lastname", "firstname", "email"]} />
      <ResourcePanel resource="students" title="Students" columns={["student_id", "lastname", "firstname", "section_id"]} />
    </PortalShell>
  );
}
