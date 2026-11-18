"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function ProfessorAttendancePage() {
  return (
    <PortalShell title="Attendance" role="professor" links={[
      { href: "/professor", label: "Overview" }, { href: "/professor/attendance", label: "Attendance" },
      { href: "/professor/schedule", label: "My Schedule" },
    ]}>
      <ResourcePanel resource="attendance" title="Attendance Records" columns={["attendance_id", "student_id", "prof_id", "subject_id", "time_in", "status", "a_status"]} />
    </PortalShell>
  );
}
