import Link from "next/link";

export default function Home() {
  return (
    <main style={{ padding: 24, color: "#fff", fontFamily: "Orbitron, sans-serif" }}>
      <h1>LabGuard</h1>
      <p>RFID attendance kiosk — migration in progress.</p>
      <Link href="/login">Staff login</Link>
    </main>
  );
}
