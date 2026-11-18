import { deleteResource, getResource, updateResource } from "@/lib/crud";

export async function GET(_request: Request, context: { params: Promise<{ resource: string; id: string }> }) {
  const { resource, id } = await context.params;
  return getResource(resource, id);
}

export async function PATCH(request: Request, context: { params: Promise<{ resource: string; id: string }> }) {
  const { resource, id } = await context.params;
  return updateResource(resource, id, request);
}

export async function DELETE(_request: Request, context: { params: Promise<{ resource: string; id: string }> }) {
  const { resource, id } = await context.params;
  return deleteResource(resource, id);
}
