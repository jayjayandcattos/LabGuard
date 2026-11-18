"use client";

import { useCallback, useEffect, useState } from "react";

type Row = Record<string, unknown>;

export default function ResourcePanel({
  resource,
  title,
  columns,
  fields,
  query,
}: {
  resource: string;
  title: string;
  columns: string[];
  fields?: string[];
  query?: string;
}) {
  const [rows, setRows] = useState<Row[]>([]);
  const [error, setError] = useState("");
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [editing, setEditing] = useState<string | null>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);

  const supportsPhoto = ["admins", "faculty", "professors", "students"].includes(resource);

  const load = useCallback(async () => {
    setError("");
    try {
      const response = await fetch(`/api/${resource}${query ? `?${query}` : ""}`, { cache: "no-store" });
      if (!response.ok) throw new Error("Request failed");
      setRows(await response.json());
    } catch {
      setError(`Could not load ${title.toLowerCase()}.`);
    }
  }, [resource, title, query]);

  useEffect(() => { load(); }, [load]);

  const editable = fields ?? [];
  const idField = rows[0] ? Object.keys(rows[0])[0] : "id";

  async function save(event: React.FormEvent) {
    event.preventDefault();
    const payload: Record<string, unknown> = {};
    for (const field of editable) if (draft[field]) payload[field] = draft[field];
    if (Object.keys(payload).length === 0) return;
    try {
      const response = await fetch(editing ? `/api/${resource}/${editing}` : `/api/${resource}`, {
        method: editing ? "PATCH" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!response.ok) throw new Error("Request failed");
      if (photoFile && editing) {
        const body = new FormData();
        body.set("resource", resource);
        body.set("id", editing);
        body.set("photo", photoFile);
        const photoResponse = await fetch("/api/uploads", { method: "POST", body });
        if (!photoResponse.ok) throw new Error("Photo upload failed");
      }
      setDraft({});
      setPhotoFile(null);
      setEditing(null);
      load();
    } catch {
      setError("Could not save record.");
    }
  }

  async function remove(id: unknown) {
    if (!window.confirm("Delete this record?")) return;
    try {
      const response = await fetch(`/api/${resource}/${id}`, { method: "DELETE" });
      if (!response.ok) throw new Error("Request failed");
      load();
    } catch {
      setError("Could not delete record.");
    }
  }

  return (
    <section className="resource-panel">
      <h2>{title}</h2>
      {error && <p role="alert" className="resource-error">{error}</p>}

      {editable.length > 0 && (
        <form onSubmit={save} className="resource-form">
          {editable.map((field) => (
            <input
              key={field}
              aria-label={field}
              placeholder={field}
              value={draft[field] ?? ""}
              onChange={(event) => setDraft((current) => ({ ...current, [field]: event.target.value }))}
            />
          ))}
          {supportsPhoto && editing && (
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp,image/gif"
              aria-label="Photo"
              onChange={(event) => setPhotoFile(event.target.files?.[0] ?? null)}
            />
          )}
          <button type="submit">{editing ? "Update" : "Add"}</button>
          {editing && <button type="button" onClick={() => { setEditing(null); setDraft({}); setPhotoFile(null); }}>Cancel</button>}
        </form>
      )}

      <div className="resource-scroll">
        <table>
          <thead>
            <tr>
              {columns.map((column) => <th key={column}>{column}</th>)}
              {editable.length > 0 && <th>Actions</th>}
            </tr>
          </thead>
          <tbody>
            {rows.map((row, index) => (
              <tr key={String(row[idField] ?? index)}>
                {columns.map((column) => (
                  <td key={column}>{formatValue(row[column])}</td>
                ))}
                {editable.length > 0 && (
                  <td>
                    <button
                      onClick={() => {
                        setEditing(String(row[idField]));
                        setDraft(Object.fromEntries(editable.map((field) => [field, String(row[field] ?? "")])));
                      }}
                    >Edit</button>
                    <button onClick={() => remove(row[idField])}>Delete</button>
                  </td>
                )}
              </tr>
            ))}
            {rows.length === 0 && <tr><td colSpan={columns.length + (editable.length ? 1 : 0)}>No records</td></tr>}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return "—";
  if (value instanceof Array) return value.map(formatValue).join(", ");
  if (typeof value === "object") return JSON.stringify(value);
  if (typeof value === "string" && /^\d{4}-\d{2}-\d{2}T/.test(value)) return value.replace("T", " ").slice(0, 19);
  return String(value);
}
