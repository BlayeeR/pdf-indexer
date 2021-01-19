import {
    Component,
    OnInit,
    ViewChildren,
    QueryList,
    ElementRef,
} from "@angular/core";
import { DocumentService } from "app/document.service";
import { PdfFile } from "@models/PdfFile";
import { MatDialog } from "@angular/material/dialog";
import { DialogDocumentComponent } from "app/dialog-document/dialog-document.component";
import { DialogDocumentConfig } from "app/dialog-document/dialog-document.config";
import { ActivatedRoute } from "@angular/router";

@Component({
    selector: "app-documents",
    templateUrl: "./documents.component.html",
    styleUrls: ["./documents.component.scss"],
})
export class DocumentsComponent implements OnInit {
    public loading: boolean = false;

    constructor(
        public data: DocumentService,
        private dialog: MatDialog,
        private route: ActivatedRoute
    ) {}

    async ngOnInit() {
        this.route.queryParams.subscribe((params) => {
            if (params["id"]) {
                this.openDocument(params["id"]);
            }
        });
        this.loading = true;
        await this.data.getDocuments();
        this.loading = false;
    }

    public async deleteDocument(document: PdfFile, index: number) {
        this.loading = true;
        await this.data.deleteDocument(document.Id).toPromise();
        this.loading = false;
        this.data.documents.splice(index, 1);
    }

    public async openDocument(documentId: number) {
        this.loading = true;
        let conf: DialogDocumentConfig = new DialogDocumentConfig(
            await this.data.getDocument(documentId)
        );
        this.loading = false;
        this.dialog.open(DialogDocumentComponent, {
            data: conf,
        });
    }
}
