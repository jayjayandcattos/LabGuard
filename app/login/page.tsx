"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

const roles = [
  { value: "admin", label: "Administrator" },
  { value: "faculty", label: "Faculty" },
  { value: "professor", label: "Professor" },
];

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState("admin");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setBusy(true);
    setError("");
    try {
      const response = await fetch("/api/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password, role }),
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        setError(data.message ?? "Login failed");
        return;
      }
      router.push(data.redirect);
      router.refresh();
    } catch {
      setError("Could not reach the server");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main style={styles.page}>
      <form onSubmit={submit} style={styles.card}>
        <h1 style={styles.title}>LabGuard</h1>
        <p style={styles.subtitle}>Staff sign in</p>

        <label style={styles.label} htmlFor="role">Role</label>
        <select id="role" value={role} onChange={(e) => setRole(e.target.value)} style={styles.input}>
          {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
        </select>

        <label style={styles.label} htmlFor="email">Email</label>
        <input
          id="email"
          type="email"
          autoComplete="username"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          style={styles.input}
          required
        />

        <label style={styles.label} htmlFor="password">Password</label>
        <input
          id="password"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          style={styles.input}
          required
        />

        {error && <p role="alert" style={styles.error}>{error}</p>}

        <button type="submit" disabled={busy} style={styles.button}>
          {busy ? "Signing in…" : "Sign in"}
        </button>
      </form>
    </main>
  );
}

const styles: Record<string, React.CSSProperties> = {
  page: {
    minHeight: "100vh",
    display: "grid",
    placeItems: "center",
    background: "linear-gradient(160deg, #2b3566, #151a33)",
    fontFamily: "Orbitron, sans-serif",
  },
  card: {
    width: 360,
    padding: 28,
    borderRadius: 16,
    background: "rgba(255,255,255,0.07)",
    border: "1px solid rgba(255,255,255,0.15)",
    color: "#fff",
    display: "flex",
    flexDirection: "column",
    gap: 8,
  },
  title: { margin: 0, fontSize: 30, letterSpacing: 2 },
  subtitle: { margin: "0 0 16px", opacity: 0.75, fontSize: 13 },
  label: { fontSize: 12, opacity: 0.8 },
  input: {
    padding: "10px 12px",
    borderRadius: 8,
    border: "1px solid rgba(255,255,255,0.25)",
    background: "rgba(0,0,0,0.25)",
    color: "#fff",
    marginBottom: 8,
  },
  button: {
    marginTop: 12,
    padding: "12px 16px",
    borderRadius: 8,
    border: 0,
    background: "#7287e5",
    color: "#fff",
    fontWeight: 700,
    cursor: "pointer",
  },
  error: { color: "#ffb4b4", fontSize: 13, margin: "4px 0" },
};
