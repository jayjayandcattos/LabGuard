"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function ProfessorSchedulePage() {
  return (
    <PortalShell title="My Schedule" role="professor" links={[
      { href: "/professor", label: "Overview" }, { href: "/professor/attendance", label: "Attendance" },
      { href: "/professor/schedule", label: "My Schedule" },
    ]}>
      <ResourcePanel resource="schedules" title="Class Schedule" columns={["schedule_id", "subject_id", "section_id", "room_id", "schedule_day", "schedule_time"]} />
    </PortalShell>
  );
}
