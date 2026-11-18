"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function AdminFacilitiesPage() {
  return (
    <PortalShell title="Facilities & Schedules" role="admin" links={[
      { href: "/admin", label: "Overview" }, { href: "/admin/users", label: "User Management" },
      { href: "/admin/facilities", label: "Facilities & Schedules" }, { href: "/admin/logs", label: "Login Logs" },
    ]}>
      <ResourcePanel resource="rooms" title="Rooms" columns={["room_number", "room_name", "status"]} fields={["room_number", "room_name", "status"]} />
      <ResourcePanel resource="schedules" title="Schedules" columns={["schedule_id", "prof_user_id", "subject_id", "section_id", "room_id", "schedule_day", "schedule_time"]} fields={["prof_user_id", "subject_id", "section_id", "room_id", "schedule_time", "schedule_end_time", "schedule_day"]} />
    </PortalShell>
  );
}
