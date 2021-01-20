import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { PdfFile } from "@models/PdfFile";
import { Date as PdfDate } from "@models/Date";
import { Amount, Vat } from "@models/Amount";
import { Info } from "@models/Info";
import { environment } from "../environments/environment";

@Injectable({
    providedIn: "root",
})
export class DocumentService {
    public documentConditions: DocumentConditions = new DocumentConditions();
    public documents: PdfFile[] = [];

    constructor(private httpClient: HttpClient) {}

    public findDates(
        fileId: number,
        searchRadius: number
    ): Observable<PdfFile> {
        return this.httpClient.get<PdfFile>(
            environment.apiUrl +
                "/files/finddates?id=" +
                fileId +
                "&searchradius=" +
                searchRadius
        );
    }

    public findAmounts(
        fileId: number,
        searchRadius: number
    ): Observable<PdfFile> {
        return this.httpClient.get<PdfFile>(
            environment.apiUrl +
                "/files/findamounts?id=" +
                fileId +
                "&searchradius=" +
                searchRadius
        );
    }

    public findInfo(fileId: number, searchRadius: number): Observable<PdfFile> {
        return this.httpClient.get<PdfFile>(
            environment.apiUrl +
                "/files/findinfo?id=" +
                fileId +
                "&searchradius=" +
                searchRadius
        );
    }

    public saveDocument(
        fileId: number,
        conditions: DocumentConditions
    ): Observable<PdfFile> {
        return this.httpClient.post<PdfFile>(
            environment.apiUrl + "/files/savedocument?id=" + fileId,
            conditions
        );
    }

    public async getDocuments() {
        this.documents = await this.httpClient
            .get<PdfFile[]>(environment.apiUrl + "/files/getdocuments")
            .toPromise();
    }

    public deleteDocument(id: number) {
        return this.httpClient.delete(
            environment.apiUrl + "/files/deletedocument?id=" + id
        );
    }

    public async getDocument(id: number): Promise<PdfFile> {
        return await this.httpClient
            .get<PdfFile>(environment.apiUrl + "/files/getdocument?id=" + id)
            .toPromise();
    }

    public mapVat(vat: Vat) {
        switch (vat) {
            case Vat.vat_0: {
                return "0%";
            }
            case Vat.vat_5: {
                return "5%";
            }
            case Vat.vat_8: {
                return "8%";
            }
            case Vat.vat_23: {
                return "23%";
            }
            case Vat.vat_zw: {
                return "zw";
            }
            case Vat.vat_np: {
                return "np";
            }
        }
    }
}

export class DocumentConditions {
    Id: number;
    Name: string;
    DateSearchRadius: number = 15;
    AmountSearchRadius: number = 10;
    InfoSearchRadius: number = 25;
    Dates: PdfDate[] = [];
    Amounts: Amount[] = [];
    Infos: Info[] = [];
    Title: string;
}

export enum Type {
    Date = 1,
    Amount = 2,
    Info = 3,
}
