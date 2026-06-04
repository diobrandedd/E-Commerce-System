import { useRef, useState } from "react";
import { X } from "lucide-react";
import { cn } from "@/app/components/ui/utils";

export function FileZone({
  label,
  hint,
  accept,
  icon: Icon,
  files,
  onFiles,
  disabled,
  multiple = true,
}: {
  label: string;
  hint: string;
  accept: string;
  icon: React.ElementType;
  files: File[];
  onFiles: (files: File[]) => void;
  disabled?: boolean;
  multiple?: boolean;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);

  const addFiles = (list: FileList | null) => {
    if (!list?.length) return;
    if (multiple) {
      const next = [...files];
      for (let i = 0; i < list.length; i++) next.push(list[i]);
      onFiles(next);
    } else {
      onFiles([list[0]]);
    }
  };

  return (
    <div
      className={cn(
        "bg-card rounded-xl border-2 border-dashed p-6 text-center transition-colors",
        dragOver ? "border-primary bg-primary/5" : "border-border",
        disabled && "opacity-60 pointer-events-none",
      )}
      onDragOver={e => {
        e.preventDefault();
        setDragOver(true);
      }}
      onDragLeave={() => setDragOver(false)}
      onDrop={e => {
        e.preventDefault();
        setDragOver(false);
        addFiles(e.dataTransfer.files);
      }}
    >
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple={multiple}
        className="hidden"
        disabled={disabled}
        onChange={e => {
          addFiles(e.target.files);
          e.target.value = "";
        }}
      />
      <Icon size={32} className="mx-auto text-muted-foreground mb-3" />
      <p className="text-sm font-semibold text-foreground">{label}</p>
      <p className="text-xs text-muted-foreground mt-1 mb-4">{hint}</p>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className="text-xs font-semibold px-4 py-2 rounded-lg border border-border hover:border-primary hover:text-primary transition-colors"
      >
        Choose files
      </button>
      {files.length > 0 && (
        <ul className="mt-4 text-left space-y-1 max-h-28 overflow-y-auto">
          {files.map((f, idx) => (
            <li key={`${f.name}-${idx}`} className="flex items-center justify-between gap-2 text-xs">
              <span className="truncate text-foreground">{f.name}</span>
              <button
                type="button"
                className="text-muted-foreground hover:text-destructive flex-shrink-0"
                onClick={() => onFiles(files.filter((_, i) => i !== idx))}
                aria-label={`Remove ${f.name}`}
              >
                <X size={14} />
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
