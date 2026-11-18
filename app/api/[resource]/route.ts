import { createResource, listResource } from "@/lib/crud";

export async function GET(request: Request, context: { params: Promise<{ resource: string }> }) {
  const { resource } = await context.params;
  return listResource(resource, new URL(request.url));
}

export async function POST(request: Request, context: { params: Promise<{ resource: string }> }) {
  const { resource } = await context.params;
  return createResource(resource, request);
}
