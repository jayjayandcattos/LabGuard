"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";

type TapResult = {
  success: boolean;
  message: string;
  time: string;
  user?: { lastname: string; firstname: string; photo: string | null; role: string };
  room?: { room_number: number; room_name: string } | null;
  status?: string;
  a_status?: string;
};

type ActiveClass = {
  active: boolean;
  message?: string;
  data?: { subject_name: string; subject_code: string; professor: string; room: string; time: string };
};

type DemoPerson = { tag: string; label: string };

export default function KioskPage() {
  const inputRef = useRef<HTMLInputElement>(null);
  const [tag, setTag] = useState("");
  const [result, setResult] = useState<TapResult | null>(null);
  const [taps, setTaps] = useState<TapResult[]>([]);
  const [clock, setClock] = useState("");
  const [active, setActive] = useState<ActiveClass | null>(null);
  const [busy, setBusy] = useState(false);
  const [demoPeople, setDemoPeople] = useState<DemoPerson[]>([]);
  const [demoTag, setDemoTag] = useState("");

  useEffect(() => {
    const tick = () =>
      setClock(
        new Intl.DateTimeFormat("en-GB", {
          timeZone: "Asia/Manila",
          hour: "2-digit", minute: "2-digit", second: "2-digit", hourCycle: "h23",
        }).format(new Date()),
      );
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, []);

  const loadActive = useCallback(async () => {
    try {
      const response = await fetch("/api/active-classes", { cache: "no-store" });
      setActive(await response.json());
    } catch {
      setActive({ active: false, message: "Could not load active class" });
    }
  }, []);

  useEffect(() => {
    loadActive();
    const id = setInterval(loadActive, 30_000);
    return () => clearInterval(id);
  }, [loadActive]);

  useEffect(() => {
    let cancelled = false;
    fetch("/api/demo/people", { cache: "no-store" })
      .then((response) => (response.ok ? response.json() : null))
      .then((data: { people?: DemoPerson[] } | null) => {
        if (cancelled || !data?.people?.length) return;
        setDemoPeople(data.people);
        setDemoTag(data.people[0].tag);
      })
      .catch(() => { /* demo mode unavailable — kiosk works normally */ });
    return () => { cancelled = true; };
  }, []);

  useEffect(() => {
    inputRef.current?.focus();
    const refocus = (event: MouseEvent) => {
      if (!(event.target as HTMLElement).closest("a,button,input,select")) inputRef.current?.focus();
    };
    document.addEventListener("click", refocus);
    return () => document.removeEventListener("click", refocus);
  }, []);

  async function sendTap(value: string) {
    const trimmed = value.trim();
    if (!trimmed || busy) return;
    setBusy(true);
    try {
      const response = await fetch("/api/tap", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ rfid_tag: trimmed }),
      });
      const data: TapResult = await response.json();
      setResult(data);
      if (data.user) setTaps((current) => [data, ...current].slice(0, 12));
      await loadActive();
    } catch {
      setResult({ success: false, message: "Could not reach the server", time: "" });
    } finally {
      setTag("");
      setBusy(false);
      inputRef.current?.focus();
    }
  }

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    await sendTap(tag);
  }

  return (
    <main className="kiosk-page">
      <header className="kiosk-header">
        <Link href="/" aria-label="LabGuard home">
          <img src="/assets/IDtap.svg" alt="LabGuard" width={54} height={54} />
        </Link>
        <div>
          <h1>LABGUARD</h1>
          <p>Smart Laboratory Attendance</p>
        </div>
        <div className="kiosk-clock" aria-label="Current time">{clock}</div>
        <Link href="/login" className="kiosk-login">STAFF LOGIN</Link>
      </header>

      <section className="content-container">
        <h2>WELCOME TO LABGUARD</h2>
        <div className="white-line" />
        <div id="description">STUDENTS CAN ONLY LOG THEIR ATTENDANCE WHEN THEIR PROFESSOR IS PRESENT.</div>
        <div className="white-line" />
      </section>

      <div className="wrapper">
        <div className="scan-container">
          {demoPeople.length > 0 ? (
            <button
              type="button"
              className="scan-id-button"
              aria-label="Simulate an ID tap"
              title="Demo: simulate a tap (physical scanner unavailable)"
              disabled={busy}
              onClick={() => sendTap(demoTag)}
            >
              <img src="/assets/IDtap.svg" alt="Tap ID to simulate attendance" className="scan-image" />
            </button>
          ) : (
            <img src="/assets/IDtap.svg" alt="Scan ID" className="scan-image" />
          )}
          <h2>PLEASE SCAN YOUR ID.</h2>
          {demoPeople.length > 0 && (
            <div className="demo-tap">
              <p className="demo-note">DEMO MODE — scanner unavailable. Choose a person, then click the ID above.</p>
              <select
                aria-label="Demo person to tap"
                value={demoTag}
                onChange={(event) => setDemoTag(event.target.value)}
              >
                {demoPeople.map((person) => (
                  <option key={person.tag} value={person.tag}>{person.label}</option>
                ))}
              </select>
            </div>
          )}
          <form onSubmit={submit}>
            <input
              ref={inputRef}
              aria-label="RFID tag"
              value={tag}
              onChange={(event) => setTag(event.target.value)}
              placeholder="RFID tag"
              autoComplete="off"
              style={{ opacity: 0, position: "absolute", pointerEvents: "none" }}
              tabIndex={-1}
            />
            <button type="submit" className="scan-submit" disabled={busy}>
              {busy ? "PROCESSING…" : "SUBMIT TAG"}
            </button>
          </form>
          {result && (
            <p role="status" className={result.success ? "tap-ok" : "tap-error"}>
              {result.message} <small>{result.time}</small>
            </p>
          )}
        </div>

        <div className={`active-class-container ${active?.active ? "visible" : ""}`}>
          <h2>ACTIVE CLASS</h2>
          {active?.data ? (
            <div className="active-class-info">
              <p><span className="label">Subject:</span> {active.data.subject_name} ({active.data.subject_code})</p>
              <p><span className="label">Professor:</span> {active.data.professor}</p>
              <p><span className="label">Room:</span> {active.data.room}</p>
              <p><span className="label">Time:</span> {active.data.time}</p>
            </div>
          ) : (
            <p>{active?.message ?? "No active class - waiting for professor"}</p>
          )}
        </div>

        <div className="right-rectangle">
          <h2>RECENT TAPS</h2>
          <div className="recent-taps-content">
            {(["professor", "student"] as const).map((role) => (
              <div className="section" key={role}>
                <div className="table">
                  <h1>{role === "professor" ? "PROFESSOR" : "STUDENTS"}</h1>
                  <div className="table-header">
                    <span>PHOTO</span><span>NAME</span><span>ROOM</span>
                    <span>CHECK IN</span><span>STATUS</span>
                  </div>
                  <div className="table-body">
                    {taps
                      .filter((tap) => tap.user?.role === role)
                      .map((tap, index) => (
                        <div className="table-row" key={`${tap.time}-${index}`}>
                          <span>{tap.user?.photo ? (
                            <img
                              src={`/api/uploads/${tap.user.photo}`}
                              alt=""
                              width={36}
                              height={36}
                              style={{ objectFit: "cover" }}
                            />
                          ) : "—"}</span>
                          <span>{tap.user?.lastname}, {tap.user?.firstname}</span>
                          <span>{tap.room ? `Room ${tap.room.room_number}` : "—"}</span>
                          <span>{tap.time}</span>
                          <span>{tap.a_status ?? tap.status ?? "—"}</span>
                        </div>
                      ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </main>
  );
}
