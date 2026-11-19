import { SutraSectionTreeNode } from "@/types/api";

/**
 * Flatten hierarchical section tree into linear reading sequence (pre-order traversal).
 */
export function flattenSections(
  tree: SutraSectionTreeNode[]
): SutraSectionTreeNode[] {
  const result: SutraSectionTreeNode[] = [];

  function traverse(nodes: SutraSectionTreeNode[]) {
    for (const node of nodes) {
      result.push(node);
      if (node.children && node.children.length > 0) {
        traverse(node.children);
      }
    }
  }

  traverse(tree);
  return result;
}

/**
 * Find a specific section by ID within a recursive tree.
 */
export function findSectionInTree(
  tree: SutraSectionTreeNode[],
  id: number
): SutraSectionTreeNode | null {
  for (const node of tree) {
    if (node.id === id) {
      return node;
    }
    if (node.children && node.children.length > 0) {
      const found = findSectionInTree(node.children, id);
      if (found) {
        return found;
      }
    }
  }
  return null;
}

/**
 * Get adjacent previous and next sections in the linear reading sequence.
 */
export function getAdjacentSections(
  flatSections: SutraSectionTreeNode[],
  currentId: number
): {
  prev: SutraSectionTreeNode | null;
  next: SutraSectionTreeNode | null;
} {
  const index = flatSections.findIndex((s) => s.id === currentId);
  if (index === -1) {
    return { prev: null, next: null };
  }

  const prev = index > 0 ? flatSections[index - 1] : null;
  const next = index < flatSections.length - 1 ? flatSections[index + 1] : null;

  return { prev, next };
}
