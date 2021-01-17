import { Injectable } from "@angular/core";

@Injectable({
    providedIn: "root",
})
export class MainService {
    constructor() {}

    public menuOptions: Array<{ Name: string; Route: string, Icon: string }> = [
        { Name: "Dodaj dokument", Route: "/add-document", Icon: "note_add" },
        { Name: "Dokumenty", Route: "/documents", Icon: "folder_open" },
    ];
}
