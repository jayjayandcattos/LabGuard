"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function AdminLogsPage() {
  return (
    <PortalShell title="Login Logs" role="admin" links={[
      { href: "/admin", label: "Overview" }, { href: "/admin/users", label: "User Management" },
      { href: "/admin/facilities", label: "Facilities & Schedules" }, { href: "/admin/logs", label: "Login Logs" },
    ]}>
      <ResourcePanel resource="logs" title="Login History" columns={["log_id", "user_id", "user_role", "user_name", "login_time", "ip_address"]} />
    </PortalShell>
  );
}
