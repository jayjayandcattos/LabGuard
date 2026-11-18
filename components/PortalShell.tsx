"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import type { Role } from "@/lib/session";

type SessionResponse = { authenticated: boolean; user?: { name: string; role: Role } };

export default function PortalShell({
  title,
  role,
  links,
  children,
}: {
  title: string;
  role: Role;
  links: Array<{ href: string; label: string }>;
  children: React.ReactNode;
}) {
  const router = useRouter();
  const [name, setName] = useState("");

  useEffect(() => {
    fetch("/api/auth/me", { cache: "no-store" })
      .then((response) => (response.ok ? response.json() : Promise.reject(new Error("unauthenticated"))))
      .then((data: SessionResponse) => {
        if (!data.user || data.user.role !== role) {
          router.replace("/login");
          return;
        }
        setName(data.user.name);
      })
      .catch(() => router.replace("/login"));
  }, [role, router]);

  async function logout() {
    await fetch("/api/auth/logout", { method: "POST" });
    router.replace("/login");
    router.refresh();
  }

  return (
    <div className="portal">
      <header className="portal-header">
        <Link href="/" className="portal-brand">LABGUARD</Link>
        <span className="portal-role">{role}</span>
        <span className="portal-user">{name}</span>
        <button onClick={logout} className="portal-logout">Sign out</button>
      </header>
      <div className="portal-body">
        <nav className="portal-nav" aria-label="Portal sections">
          {links.map((link) => (
            <Link key={link.href} href={link.href}>{link.label}</Link>
          ))}
          <Link href="/">RFID Kiosk</Link>
        </nav>
        <main className="portal-content">
          <h1>{title}</h1>
          {children}
        </main>
      </div>
    </div>
  );
}
